<?php

declare(strict_types=1);

namespace app\shared\Forum\Service;

use app\shared\Forum\Dto\MemberData;
use app\shared\Forum\Dto\TopicData;
use app\shared\Forum\Infrastructure\ForumLoginRequiredException;
use DateTimeImmutable;
use DateTimeZone;
use DOMElement;
use DOMXPath;
use RuntimeException;

/**
 * Parses a phpBB topic page (forum.awd.ru) into a TopicData DTO.
 */
final class ForumHtmlParser extends ForumPageDomParser
{
    public function parse(int $topicId, string $sourceUrl, string $html, ?DateTimeImmutable $now = null): TopicData
    {
        $xpath = $this->loadXPath($html);

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
}
