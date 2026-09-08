<?php

declare(strict_types=1);

namespace app\shared\Forum\Service;

use app\shared\Forum\Dto\MemberData;
use app\shared\Forum\Infrastructure\ForumLoginRequiredException;
use app\shared\Forum\Infrastructure\ForumPageNotFoundException;
use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

/**
 * Parses phpBB memberlist profile pages (forum.awd.ru) into a MemberData DTO.
 *
 * The profile is split into tabs; two pages are fetched per member:
 * - main page (memberlist.php?mode=viewprofile&u=..., the "Контакты" tab):
 *   h2 "Профиль пользователя <name>", rank under the avatar, dl.left-box
 *   details with the profile fields (Страна, Город, Возраст, Пол, Галерея);
 * - statistics tab (page=7): dl.details with Зарегистрирован,
 *   Последнее посещение, Всего сообщений, Фотографий.
 */
final class MemberProfilePageParser extends ForumPageDomParser
{
    public function parseMain(int $memberId, string $sourceUrl, string $html): MemberData
    {
        $xpath = $this->loadXPath($html);

        $body = $this->bodyText($xpath);
        if (str_contains($body, 'вы должны быть авторизованы')) {
            throw new ForumLoginRequiredException('Profile is available to authorized users only.');
        }
        if (str_contains($body, 'Запрашиваемого пользователя не существует')) {
            throw new ForumPageNotFoundException('Member does not exist: ' . $sourceUrl);
        }

        $name = '';
        $title = $this->cleanText($this->firstText($xpath, [
            '//div[contains(concat(" ", normalize-space(@id), " "), " page-body ")]/h2',
        ]));
        if (preg_match('~^Профиль пользователя\s+(.+)$~u', $title, $m) === 1) {
            $name = $m[1];
        }
        if ($name === '') {
            throw new RuntimeException('Member name was not found.');
        }

        $fields = $this->parseDetailFields($xpath, '//dl[contains(concat(" ", normalize-space(@class), " "), " left-box ")]');

        $rankName = null;
        $avatarUrl = null;
        $box = $this->firstElement($xpath, ['//dl[contains(concat(" ", normalize-space(@class), " "), " left-box ")][./dt/img]']);
        if ($box !== null) {
            $dd = $this->firstElement($xpath, ['./dd'], $box);
            $rankName = $dd === null ? null : ($this->cleanText($dd->textContent) ?: null);
            $avatar = $this->firstElement($xpath, ['./dt/img[@src]', './dt//img[@src]'], $box);
            if ($avatar !== null) {
                $avatarUrl = $this->absoluteUrl($avatar->getAttribute('src'), $sourceUrl);
            }
        }

        return new MemberData(
            $memberId,
            $sourceUrl,
            $name,
            $avatarUrl,
            $rankName,
            null,
            null,
            $fields['Город'] ?? null,
            null,
            null,
            $this->parseIntOrNull($fields['Возраст'] ?? null),
            null,
            null,
            $fields['Пол'] ?? null,
            $fields,
        );
    }

    /**
     * Parses the statistics tab (page=7) and returns its raw fields:
     * Зарегистрирован, Последнее посещение, Всего сообщений, Фотографий.
     *
     * @return array{registered_at: ?string, last_visit_at: ?string, messages_count: ?int, photos_count: ?int}
     */
    public function parseStatistics(string $sourceUrl, string $html, ?DateTimeImmutable $now = null): array
    {
        $xpath = $this->loadXPath($html);
        $now ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $fields = $this->parseDetailFields(
            $xpath,
            '//div[contains(concat(" ", normalize-space(@class), " "), " panel ")][.//h3[contains(text(), "Статистика")]]//dl[contains(concat(" ", normalize-space(@class), " "), " details ")]'
        );

        return [
            'registered_at' => $this->normalizeDateTime($fields['Зарегистрирован'] ?? '', $now),
            'last_visit_at' => $this->normalizeDateTime($fields['Последнее посещение'] ?? '', $now),
            'messages_count' => $this->parseIntOrNull($fields['Всего сообщений'] ?? null),
            'photos_count' => $this->parseIntOrNull($fields['Фотографий'] ?? null),
        ];
    }

    /**
     * Collects dt/dd pairs of a details block into a label => value map.
     *
     * @return array<string, string>
     */
    private function parseDetailFields(DOMXPath $xpath, string $query): array
    {
        $result = [];
        $nodes = $xpath->query($query);
        if ($nodes === false) {
            return $result;
        }
        /** @var DOMElement $dl */
        foreach ($nodes as $dl) {
            $dt = null;
            foreach ($dl->childNodes as $child) {
                if ($child->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }
                if ($child->tagName === 'dt') {
                    $dt = $child;
                } elseif ($child->tagName === 'dd' && $dt !== null) {
                    $label = mb_substr($this->cleanText($dt->textContent), 0, -1, 'UTF-8');
                    $value = $this->cleanText($child->textContent);
                    if ($label !== '' && $value !== '') {
                        $result[$label] = $value;
                    }
                    $dt = null;
                }
            }
        }
        return $result;
    }

    /**
     * Normalizes profile dates: "24 дек 2011, 22:42", "Сегодня, 17:28",
     * "Вчера, 20:10" (site timezone Europe/Moscow, stored as-is in site time).
     */
    private function normalizeDateTime(string $value, DateTimeImmutable $now): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $siteNow = $now->setTimezone(new DateTimeZone(self::DATE_SITE));
        if (preg_match('/(Сегодня|Вчера),?\s*(\d{1,2}:\d{2})/u', $value, $m) === 1) {
            $day = $siteNow->modify($m[1] === 'Вчера' ? '-1 day' : 'today');
            return $this->formatSiteDate($day, $m[2]);
        }
        return $this->normalizeDate($value, $now);
    }

    private function parseIntOrNull(?string $value): ?int
    {
        if ($value === null || preg_match('/\d+/u', $value, $m) !== 1) {
            return null;
        }
        return (int)$m[0];
    }

    private function bodyText(DOMXPath $xpath): string
    {
        $nodes = $xpath->query('//body');
        $node = $nodes === false ? null : $nodes->item(0);
        return $node === null ? '' : $this->cleanText($node->textContent);
    }
}
