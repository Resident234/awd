<?php

declare(strict_types=1);

namespace app\shared\Forum\Service;

use app\shared\Forum\Contract\ForumRepositoryInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * The tunables of the parsers, stored in parser_config.
 *
 * The row of parser_config holds two kinds of values. `base_url`, `t_from`,
 * `t_to` and `is_active` describe the entity range one scan walks, so they
 * differ per row. The HTTP behaviour, the page cap and the timezone of the
 * source site belong to every scan at once: parser_config carries them on
 * each row, the settings page writes the same values into all of them, and a
 * scan service reads them from the row it loads anyway.
 *
 * DEFAULTS are what the code falls back to while the table is empty; the
 * migration seeds the very same numbers, so the two never disagree.
 */
final class ParserSettingsService
{
    public const DEFAULTS = [
        'http_timeout' => 30,
        'http_retries' => 3,
        'http_delay_microseconds' => 500000,
        'http_max_redirects' => 5,
        'http_banned_statuses' => '429,500,502,503,504',
        'login_url' => 'https://forum.awd.ru/ucp.php?mode=login',
        'max_pages' => 10000,
        'source_timezone' => 'Europe/Moscow',
    ];

    /**
     * Bounds of the tunables the settings form shows: the label of the field,
     * the interval it accepts and the unit the form prints after it.
     */
    private const BOUNDS = [
        'http_timeout' => ['label' => 'Ожидание ответа страницы', 'min' => 1, 'max' => 300, 'unit' => 'секунд'],
        'http_retries' => ['label' => 'Повторов запроса', 'min' => 0, 'max' => 10, 'unit' => 'раз'],
        'http_delay_microseconds' => ['label' => 'Пауза перед повтором', 'min' => 0, 'max' => 5000000, 'unit' => 'мкс'],
        'http_max_redirects' => ['label' => 'Переходов по редиректу', 'min' => 0, 'max' => 20, 'unit' => 'раз'],
        'max_pages' => ['label' => 'Страниц на одну сущность', 'min' => 1, 'max' => 100000, 'unit' => 'страниц'],
    ];

    /** @var array<string, mixed>|null the read values, so one process hits the table once */
    private ?array $cached = null;

    /**
     * The labels of the tunables that are not numbers, so the form and the
     * messages about them say the same thing.
     */
    private const TEXT_LABELS = [
        'http_banned_statuses' => 'Коды ответа, на которые запрос повторяется',
        'login_url' => 'Адрес входа на форум',
        'source_timezone' => 'Часовой пояс источника',
    ];

    public function __construct(private readonly ForumRepositoryInterface $repository)
    {
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::BOUNDS as $code => $bound) {
            $labels[$code] = $bound['label'];
        }

        return $labels + self::TEXT_LABELS;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function bounds(): array
    {
        return self::BOUNDS;
    }

    /**
     * The stored tunables with the defaults filling in whatever the table does
     * not carry yet.
     *
     * @return array<string, mixed>
     */
    public function tunables(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $stored = $this->repository->parserTunables();
        if ($stored === null) {
            return $this->cached = self::DEFAULTS;
        }

        $tunables = [];
        foreach (self::DEFAULTS as $code => $default) {
            $tunables[$code] = isset($stored[$code]) && (string)$stored[$code] !== ''
                ? $stored[$code]
                : $default;
        }

        return $this->cached = $tunables;
    }

    /**
     * The entity ranges of the scans, ready for the form.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array
    {
        $rows = [];
        foreach ($this->repository->parserRows() as $row) {
            $rows[] = [
                'id' => (int)$row['id'],
                'code' => (string)$row['code'],
                'baseUrl' => (string)$row['base_url'],
                'tFrom' => (int)$row['t_from'],
                'tTo' => (int)$row['t_to'],
                'isActive' => $row['is_active'] === true || $row['is_active'] === 't',
                'lastRunAt' => $row['last_run_at'],
            ];
        }

        return $rows;
    }

    /**
     * The HTTP status codes a retry answers to, as the client wants them.
     *
     * @return int[]
     */
    public function bannedStatuses(): array
    {
        return self::parseStatuses((string)$this->tunables()['http_banned_statuses']);
    }

    /**
     * Checks the submitted form and stores the tunables together with the
     * entity ranges.
     *
     * @param array<string, mixed> $submitted
     * @param array<int, array<string, mixed>> $submittedRows
     * @throws InvalidArgumentException on the first value that does not fit
     */
    public function save(array $submitted, array $submittedRows): void
    {
        $tunables = [];
        foreach (self::BOUNDS as $code => $bound) {
            $raw = trim((string)(isset($submitted[$code]) ? $submitted[$code] : ''));
            if ($raw === '') {
                throw new InvalidArgumentException("«{$bound['label']}» нельзя оставить пустым.");
            }

            if (preg_match('/^\d+$/', $raw) !== 1) {
                throw new InvalidArgumentException("«{$bound['label']}» надо задать целым числом.");
            }

            $number = (int)$raw;
            if ($number < (int)$bound['min'] || $number > (int)$bound['max']) {
                throw new InvalidArgumentException(sprintf(
                    '«%s» принимает значения от %d до %d, передано %d.',
                    $bound['label'],
                    (int)$bound['min'],
                    (int)$bound['max'],
                    $number,
                ));
            }

            $tunables[$code] = $number;
        }

        $statuses = self::parseStatuses(trim((string)(isset($submitted['http_banned_statuses'])
            ? $submitted['http_banned_statuses']
            : '')));
        if ($statuses === []) {
            throw new InvalidArgumentException(
                'Коды ответов, на которые запрос повторяется, надо перечислить через запятую.',
            );
        }

        $tunables['http_banned_statuses'] = implode(',', $statuses);
        $tunables['login_url'] = self::requireUrl(
            (string)(isset($submitted['login_url']) ? $submitted['login_url'] : ''),
            self::TEXT_LABELS['login_url'],
        );

        $timezone = trim((string)(isset($submitted['source_timezone']) ? $submitted['source_timezone'] : ''));
        if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidArgumentException("Часового пояса «{$timezone}» не существует.");
        }

        $tunables['source_timezone'] = $timezone;

        $this->repository->saveParserSettings($tunables, $this->ranges($submittedRows), gmdate('Y-m-d H:i:s'));
        $this->cached = null;
    }

    /**
     * The entity ranges of the rows the form submitted.
     *
     * @param array<int, array<string, mixed>> $submittedRows
     * @return array<int, array<string, mixed>>
     */
    private function ranges(array $submittedRows): array
    {
        $ranges = [];
        foreach ($submittedRows as $code => $row) {
            $tFrom = (int)(isset($row['tFrom']) ? $row['tFrom'] : 0);
            $tTo = (int)(isset($row['tTo']) ? $row['tTo'] : 0);
            if ($tFrom < 0 || $tTo < 0 || $tFrom > $tTo) {
                throw new InvalidArgumentException(
                    "Диапазон «{$code}» должен быть от 0 и вверх, начало не больше конца.",
                );
            }

            $ranges[] = [
                'id' => (int)$row['id'],
                'baseUrl' => self::requireUrl((string)$row['baseUrl'], "Адрес сущности «{$code}»"),
                'tFrom' => $tFrom,
                'tTo' => $tTo,
                'isActive' => isset($row['isActive']) && $row['isActive'] === '1',
            ];
        }

        return $ranges;
    }

    /**
     * @throws InvalidArgumentException when the address is not an http(s) URL
     */
    private static function requireUrl(string $value, string $label): string
    {
        $url = trim($value);
        if (filter_var($url, FILTER_VALIDATE_URL) === false || !preg_match('#^https?://#i', $url)) {
            throw new InvalidArgumentException("«{$label}» должен быть адресом http(s).");
        }

        return $url;
    }

    /**
     * The comma separated status list as the numbers cURL compares.
     *
     * @return int[]
     */
    private static function parseStatuses(string $list): array
    {
        if ($list === '') {
            return [];
        }

        $statuses = [];
        foreach (explode(',', $list) as $part) {
            $code = trim($part);
            if (preg_match('/^[1-5]\d{2}$/', $code) !== 1) {
                throw new InvalidArgumentException("Код ответа «{$code}» не похож на номер статуса HTTP.");
            }

            $statuses[] = (int)$code;
        }

        return array_values(array_unique($statuses));
    }
}
