<?php

declare(strict_types=1);

namespace app\shared\Forum\Service;

use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;

/**
 * Shared low-level helpers for parsing phpBB (forum.awd.ru) HTML pages:
 * DOM loading, date normalization, url normalization and small XPath utils.
 */
abstract class ForumPageDomParser
{
    protected const MONTHS = [
        'янв' => 1, 'фев' => 2, 'мар' => 3, 'апр' => 4, 'май' => 5, 'мая' => 5,
        'июн' => 6, 'июл' => 7, 'авг' => 8, 'сен' => 9, 'окт' => 10, 'ноя' => 11, 'дек' => 12,
    ];

    protected const DATE_SITE = 'Europe/Moscow';

    protected function loadXPath(string $html): DOMXPath
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
        return new DOMXPath($document);
    }

    /**
     * Normalizes a phpBB post date into "Y-m-d H:i:s" (site timezone Europe/Moscow).
     * Supports "27 авг 2026, 19:42", "Вчера, 19:42", "Сегодня, 09:05", "31.12.2024, 23:59".
     */
    protected function normalizeDate(string $value, DateTimeImmutable $now): ?string
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

    protected function formatSiteDate(DateTimeImmutable $day, string $time): string
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
     * Turns relative hrefs into absolute ones and drops the phpBB sid parameter.
     */
    protected function absoluteUrl(string $url, string $base): string
    {
        if (preg_match('~^https?://~i', $url) === 1) {
            $absolute = $url;
        } else {
            $parts = parse_url($base);
            $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'forum.awd.ru');
            if (str_starts_with($url, '//')) {
                $absolute = ($parts['scheme'] ?? 'https') . ':' . $url;
            } elseif (str_starts_with($url, '/')) {
                $absolute = $origin . $url;
            } else {
                $path = preg_replace('~/[^/]*$~u', '', $parts['path'] ?? '') ?: '';
                $absolute = $origin . $path . '/' . ltrim($url, './');
            }
        }
        return $this->stripSessionId($absolute);
    }

    /**
     * Drops the phpBB session id parameter from stored URLs:
     * ...?mode=viewprofile&u=23071&sid=... -> ...?mode=viewprofile&u=23071
     */
    protected function stripSessionId(string $url): string
    {
        $parts = parse_url($url);
        if (!isset($parts['query'])) {
            return $url;
        }
        parse_str($parts['query'], $query);
        if (!array_key_exists('sid', $query)) {
            return $url;
        }
        unset($query['sid']);
        $parts['query'] = http_build_query($query);
        if ($parts['query'] === '') {
            unset($parts['query']);
        }
        return $this->buildUrl($parts);
    }

    /**
     * @param array{scheme?: string, host?: string, port?: int, path?: string, query?: string, fragment?: string} $parts
     */
    private function buildUrl(array $parts): string
    {
        $url = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
        if (isset($parts['port'])) {
            $url .= ':' . $parts['port'];
        }
        $url .= $parts['path'] ?? '';
        if (isset($parts['query'])) {
            $url .= '?' . $parts['query'];
        }
        if (isset($parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
        }
        return $url;
    }

    protected function firstElement(DOMXPath $xpath, array $queries, ?DOMNode $context = null): ?DOMElement
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

    protected function firstText(DOMXPath $xpath, array $queries, ?DOMNode $context = null): string
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

    protected function cleanText(string $text): string
    {
        return trim((string)preg_replace('/[\s\x{00A0}]+/u', ' ', $text));
    }

    protected function innerHtml(DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument?->saveHTML($child) ?? '';
        }
        return trim($html);
    }

    protected function htmlToText(string $html): string
    {
        $text = html_entity_decode(strip_tags(preg_replace('/\s*<(br\s*\/?)\s*>\s*/iu', "\n", $html) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string)preg_replace('/[ \t]+/u', ' ', $text);
        $text = (string)preg_replace('/\R{3,}/u', "\n\n", $text);
        return trim($text);
    }
}
