<?php

declare(strict_types=1);

namespace app\shared\Forum\Service;

use app\shared\Forum\Dto\MemberData;
use app\shared\Forum\Dto\PostData;
use DateTimeImmutable;
use DateTimeZone;
use DOMElement;
use DOMXPath;
use RuntimeException;

/**
 * Parses a single phpBB topic page (forum.awd.ru) into post DTOs.
 *
 * Every page of a topic (including the first one) contains:
 * - posts: div elements with id="p123456" and class="post" and a dl.postprofile inside;
 * - pagination: div.pagination with links to all other pages (start=N offset)
 *   and a "Страница N из M" counter.
 */
final class ForumPostPageParser extends ForumPageDomParser
{
    /**
     * @return PostData[]
     */
    public function parse(int $topicId, string $pageUrl, string $html, ?DateTimeImmutable $now = null): array
    {
        $xpath = $this->loadXPath($html);
        $now ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $posts = [];
        foreach (
            $xpath->query(
                '//div[starts-with(@id, "p")][contains(concat(" ", normalize-space(@class), " "), " post ")]'
                . '[.//*[contains(concat(" ", normalize-space(@class), " "), " postprofile ")]]'
            ) ?: [] as $post
        ) {
            if (!$post instanceof DOMElement) {
                continue;
            }
            $postId = (int)ltrim($post->getAttribute('id'), 'p');
            if ($postId <= 0) {
                continue;
            }
            $posts[] = $this->parsePost($post, $xpath, $topicId, $postId, $pageUrl, $now);
        }
        return $posts;
    }

    /**
     * Extracts the next page url from the div.pagination block.
     * phpBB lists all page links as ./viewtopic.php?t=...&start=N; the next
     * page is the link with the smallest start offset greater than the
     * current page offset (links are in ascending order, so the first one
     * after the current offset wins).
     */
    public function parseNextPageUrl(string $pageUrl, string $html): ?string
    {
        $xpath = $this->loadXPath($html);

        parse_str((string)parse_url($pageUrl, PHP_URL_QUERY), $currentQuery);
        $currentStart = (int)($currentQuery['start'] ?? 0);

        $nextStart = null;
        /** @var DOMElement $link */
        foreach ($xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " pagination ")]//a[contains(@href, "viewtopic.php")]') ?: [] as $link) {
            parse_str((string)parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
            if (!isset($query['start']) || !isset($query['t'])) {
                continue;
            }
            $start = (int)$query['start'];
            if ($start > $currentStart && ($nextStart === null || $start < $nextStart)) {
                $nextStart = $start;
            }
        }
        if ($nextStart === null) {
            return null;
        }
        return $this->absoluteUrl('viewtopic.php?t=' . (int)$currentQuery['t'] . '&start=' . $nextStart, $pageUrl);
    }

    private function parsePost(
        DOMElement $post,
        DOMXPath $xpath,
        int $topicId,
        int $postId,
        string $pageUrl,
        DateTimeImmutable $now,
    ): PostData {
        $body = $this->firstElement($xpath, [
            './/*[contains(concat(" ", normalize-space(@class), " "), " postbody ")]',
        ], $post);

        $title = $this->cleanText($this->firstText($xpath, [
            './h3/a',
            './/h3/a',
        ], $body ?? $post));

        $number = null;
        $numberText = $this->firstText($xpath, [
            './/div[contains(text(), "Сообщение:")]//a',
        ], $body ?? $post);
        if (preg_match('/#(\d+)/u', trim($numberText), $m) === 1) {
            $number = (int)$m[1];
        }

        $dateText = $this->cleanText($this->firstText($xpath, [
            './/*[contains(concat(" ", normalize-space(@class), " "), " author ")]',
        ], $body ?? $post));
        $postedAt = $this->normalizeDate($dateText, $now);

        $content = $this->firstElement($xpath, [
            './/*[contains(concat(" ", normalize-space(@class), " "), " content ")]',
        ], $body ?? $post);
        if ($content === null) {
            throw new RuntimeException('Post content block was not found.');
        }
        $contentHtml = $this->innerHtml($content);
        $contentText = $this->htmlToText($contentHtml);

        $profile = $this->firstElement($xpath, [
            './/*[contains(concat(" ", normalize-space(@class), " "), " postprofile ")]',
        ], $post);
        $author = $profile === null ? null : $this->parseMember($xpath, $profile, $pageUrl);

        return new PostData(
            $postId,
            $topicId,
            $author?->id,
            $number,
            $title,
            $postedAt,
            $contentHtml,
            $contentText,
            $this->absoluteUrl('viewtopic.php?p=' . $postId . '#p' . $postId, $pageUrl),
            $author,
        );
    }

    private function parseMember(DOMXPath $xpath, DOMElement $profile, string $pageUrl): ?MemberData
    {
        $nameLink = null;
        foreach ($xpath->query('.//dt//a[contains(@href, "memberlist.php")]', $profile) ?: [] as $link) {
            if (!$link->hasChildNodes() || !$this->firstElement($xpath, ['.//img'], $link)) {
                $nameLink = $link;
                break;
            }
        }
        if (!$nameLink instanceof DOMElement) {
            return null;
        }
        $href = (string)$nameLink->getAttribute('href');
        parse_str((string)parse_url($href, PHP_URL_QUERY), $query);
        $id = (int)($query['u'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $profileUrl = $this->absoluteUrl($href, $pageUrl);
        $name = $this->cleanText($nameLink->textContent);
        if ($name === '') {
            return null;
        }

        $avatarUrl = null;
        $avatar = $this->firstElement($xpath, ['.//dt//img[@src]'], $profile);
        if ($avatar instanceof DOMElement) {
            $avatarUrl = $this->absoluteUrl($avatar->getAttribute('src'), $pageUrl);
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
                // The rank dd may contain a "quote selected text" link after the rank itself.
                $rankText = $this->cleanText($this->firstText($xpath, ['./text()'], $dd));
                $rankName = $rankText !== '' ? $rankText : $this->cleanText($dd->textContent);
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
}
