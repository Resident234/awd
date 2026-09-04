<?php

declare(strict_types=1);

namespace app\shared\Forum\Service;

use app\shared\Forum\Dto\MemberData;
use app\shared\Forum\Dto\TopicData;
use app\shared\Forum\Infrastructure\ForumLoginRequiredException;
use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;

/**
 * Parses a phpBB topic page (forum.awd.ru) into a TopicData DTO.
 */
final class ForumHtmlParser
{
    private const MONTHS = [
        'янв' => 1, 'фев' => 2, 'мар' => 3, 'апр' => 4, 'май' => 5, 'мая' => 5,
        'июн' => 6, 'июл' => 7, 'авг' => 8, 'сен' => 9, 'окт' => 10, 'ноя' => 11, 'дек' => 12,
    ];

    private const DATE_SITE = 'Europe/Moscow';

    public function parse(int $topicId, string $sourceUrl, string $html, ?DateTimeImmutable $now = null): TopicData
    {
        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        if (!$loaded || $document->documentElement === null) {
            throw new RuntimeException('Forum page HTML is not loadable.');
        }
        $xpath = new DOMXPath($document);

        $title = $this->cleanText($this->firstText($xpath, [
            '//h2[contains(concat(" ", normalize-space(@class), " "), " topic-title ")]/a',
            '//h2[contains(concat(" ", normalize-space(@class), " "), " topic-title ")]',
            '//div[contains(@class, "postbody")]//h3[contains(@class, "first")]/a',
            '//h1',
        ]));
        if ($title === '') {
            $body = $this->cleanText($this->firstText($xpath, ['//body']));
            if (str_contains($body, 'вы должны быть авторизованы')) {
                throw new ForumLoginRequiredException('Topic is available to authorized users only.');
            }
            throw new RuntimeException('Topic title was not found.');
        }

        $post = $this->firstElement($xpath, [
            '//*[contains(concat(" ", normalize-space(@class), " "), " post ")][.//*[contains(concat(" ", normalize-space(@class), " "), " postprofile ")]]',
            '//*[contains(concat(" ", normalize-space(@class), " "), " post ")]',
        ]);
        if ($post === null) {
            throw new RuntimeException('Topic post block was not found.');
        }

        $content = $this->firstElement($xpath, [
            './/*[contains(concat(" ", normalize-space(@class), " "), " content ")]',
        ], $post);
        if ($content === null) {
            throw new RuntimeException('Topic content block was not found.');
        }
        $contentHtml = $this->innerHtml($content);
        $contentText = $this->htmlToText($contentHtml);
        $imageUrls = $this->collectImageUrls($xpath, $content, $sourceUrl);

        $profile = $this->firstElement($xpath, [
            './/*[contains(concat(" ", normalize-space(@class), " "), " postprofile ")]',
        ], $post);
        if ($profile === null) {
            throw new RuntimeException('Topic author profile block was not found.');
        }
        $author = $this->parseMember($xpath, $profile, $sourceUrl);

        $dateText = $this->cleanText($this->firstText($xpath, [
            './/*[contains(concat(" ", normalize-space(@class), " "), " author ")]',
        ], $post));
        $publishedAt = $this->normalizeDate($dateText, $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC')));

        return new TopicData(
            $topicId,
            $sourceUrl,
            $title,
            $publishedAt,
            $contentHtml,
            $contentText,
            $imageUrls,
            $author,
        );
    }

    private function parseMember(DOMXPath $xpath, DOMElement $profile, string $topicUrl): MemberData
    {
        $nameLink = null;
        foreach ($xpath->query('.//dt//a[contains(@href, "memberlist.php")]', $profile) ?: [] as $link) {
            if (!$link->hasChildNodes() || !$this->firstElement($xpath, ['.//img'], $link)) {
                $nameLink = $link;
                break;
            }
        }
        if (!$nameLink instanceof DOMElement) {
            throw new RuntimeException('Member profile link was not found.');
        }
        $href = (string)$nameLink->getAttribute('href');
        parse_str((string)parse_url($href, PHP_URL_QUERY), $query);
        $id = (int)($query['u'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('Member id was not found.');
        }
        $profileUrl = $this->absoluteUrl($href, $topicUrl);
        $name = $this->cleanText($nameLink->textContent);
        if ($name === '') {
            throw new RuntimeException('Member name was not found.');
        }

        $avatarUrl = null;
        $avatar = $this->firstElement($xpath, ['.//dt//img[@src]'], $profile);
        if ($avatar instanceof DOMElement) {
            $avatarUrl = $this->absoluteUrl($avatar->getAttribute('src'), $topicUrl);
        }

        $rankName = null;
        $fields = [];
        foreach ($xpath->query('./dd', $profile) ?: [] as $dd) {
            if (!$dd instanceof DOMElement) {
                continue;
            }
            $labelNode = $this->firstElement($xpath, ['./strong'], $dd);
            if ($labelNode instanceof DOMElement) {
                $label = $this->cleanText($labelNode->textContent);
                $value = $this->cleanText(str_replace($labelNode->textContent, '', $dd->textContent));
                if ($label !== '' && $value !== '') {
                    $fields[mb_substr($label, 0, -1, 'UTF-8')] = $value;
                }
            } elseif ($rankName === null && $this->cleanText($dd->textContent) !== '') {
                $rankName = $this->cleanText($dd->textContent);
            }
        }

        $int = static function (?string $value): ?int {
            if ($value === null || !preg_match('/\d+/u', $value, $m)) {
                return null;
            }
            return (int)$m[0];
        };

        $registeredOn = null;
        if (!empty($fields['Регистрация'])) {
            $date = DateTimeImmutable::createFromFormat('!d.m.Y', $fields['Регистрация'], new DateTimeZone('UTC'));
            $registeredOn = $date !== false ? $date->format('Y-m-d') : null;
        }

        return new MemberData(
            $id,
            $profileUrl,
            $name,
            $avatarUrl,
            $rankName,
            $int($fields['Сообщения'] ?? null),
            $registeredOn,
            $fields['Город'] ?? null,
            $int($fields['Благодарил (а)'] ?? null),
            $int($fields['Поблагодарили'] ?? null),
            $int($fields['Возраст'] ?? null),
            $int($fields['Страны'] ?? null),
            $int($fields['Отчеты'] ?? null),
            $fields['Пол'] ?? null,
            $fields,
        );
    }

    /**
     * Normalizes a phpBB post date into "Y-m-d H:i:s" (UTC).
     * Supports "27 авг 2026, 19:42", "Вчера, 19:42", "Сегодня, 09:05".
     */
    private function normalizeDate(string $value, DateTimeImmutable $now): ?string
    {
        $value = trim((string)preg_replace('/\s+/u', ' ', $value));
        if ($value === '') {
            return null;
        }
        $siteNow = $now->setTimezone(new DateTimeZone(self::DATE_SITE));
        if (preg_match('/(Сегодня|Вчера),?\s*(\d{1,2}:\d{2})/u', $value, $m) === 1) {
            $day = $siteNow->modify($m[1] === 'Вчера' ? '-1 day' : 'today');
            return $this->formatSiteDate($day, $m[2]);
        }
        if (preg_match('/(\d{1,2})\s+([а-яё]+)\s+(\d{4}),?\s+(\d{1,2}:\d{2})/ui', $value, $m) === 1) {
            $month = self::MONTHS[mb_strtolower($m[2], 'UTF-8')] ?? null;
            if ($month !== null) {
                $date = DateTimeImmutable::createFromFormat(
                    '!Y-n-j G:i',
                    sprintf('%d-%d-%d %s', (int)$m[3], $month, (int)$m[1], $m[4]),
                    new DateTimeZone(self::DATE_SITE)
                );
                if ($date !== false) {
                    return $date->format('Y-m-d H:i:s');
                }
            }
        }
        if (preg_match('/(\d{1,2}\.\d{1,2}\.\d{4}),?\s+(\d{1,2}:\d{2})/u', $value, $m) === 1) {
            $date = DateTimeImmutable::createFromFormat('!d.m.Y G:i', $m[1] . ' ' . $m[2], new DateTimeZone(self::DATE_SITE));
            if ($date !== false) {
                return $date->format('Y-m-d H:i:s');
            }
        }
        return null;
    }

    private function formatSiteDate(DateTimeImmutable $day, string $time): string
    {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d G:i',
            $day->format('Y-m-d') . ' ' . $time,
            new DateTimeZone(self::DATE_SITE)
        );
        if ($date === false) {
            return $day->format('Y-m-d') . ' ' . $time . ':00';
        }
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @return string[]
     */
    private function collectImageUrls(DOMXPath $xpath, DOMElement $content, string $sourceUrl): array
    {
        $urls = [];
        foreach ($xpath->query('.//img', $content) ?: [] as $image) {
            if (!$image instanceof DOMElement) {
                continue;
            }
            $src = trim($image->getAttribute('data-src') ?: $image->getAttribute('src'));
            if ($src === '') {
                continue;
            }
            $absolute = $this->absoluteUrl($src, $sourceUrl);
            if (!preg_match('~\.(?:gif|jpe?g|png|webp)(?:[?#]|$)~ui', $absolute)) {
                continue;
            }
            $urls[] = $absolute;
        }
        return array_values(array_unique($urls));
    }

    private function htmlToText(string $html): string
    {
        $text = html_entity_decode(strip_tags(preg_replace('/\s*<(br\s*\/?)\s*>\s*/iu', "\n", $html) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string)preg_replace('/[ \t]+/u', ' ', $text);
        $text = (string)preg_replace('/\R{3,}/u', "\n\n", $text);
        return trim($text);
    }

    private function firstElement(DOMXPath $xpath, array $queries, ?DOMNode $context = null): ?DOMElement
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query, $context);
            $node = $nodes === false ? null : $nodes->item(0);
            if ($node instanceof DOMElement) {
                return $node;
            }
        }
        return null;
    }

    private function firstText(DOMXPath $xpath, array $queries, ?DOMNode $context = null): string
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query, $context);
            $node = $nodes === false ? null : $nodes->item(0);
            if ($node instanceof DOMNode) {
                return $node->textContent;
            }
        }
        return '';
    }

    private function cleanText(string $text): string
    {
        return trim((string)preg_replace('/[\s\x{00A0}]+/u', ' ', $text));
    }

    private function innerHtml(DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument?->saveHTML($child) ?? '';
        }
        return trim($html);
    }

    private function absoluteUrl(string $url, string $base): string
    {
        if (preg_match('~^https?://~i', $url) === 1) {
            return $url;
        }
        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'forum.awd.ru');
        if (str_starts_with($url, '//')) {
            return ($parts['scheme'] ?? 'https') . ':' . $url;
        }
        if (str_starts_with($url, '/')) {
            return $origin . $url;
        }
        $path = preg_replace('~/[^/]*$~u', '', $parts['path'] ?? '') ?: '';
        return $origin . $path . '/' . ltrim($url, './');
    }
}
