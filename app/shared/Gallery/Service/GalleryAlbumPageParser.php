<?php

declare(strict_types=1);

namespace app\shared\Gallery\Service;

use app\shared\Forum\Infrastructure\ForumLoginRequiredException;
use app\shared\Forum\Infrastructure\ForumPageNotFoundException;
use app\shared\Gallery\Dto\AlbumData;
use app\shared\Gallery\Dto\GalleryImageData;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

/**
 * Parses a phpBB gallery album page (forum.awd.ru/gallery) into an AlbumData
 * DTO with all images of the page.
 *
 * Album page structure (prosilver):
 * - title: h2 > a[href*="album.php"] inside #page-body;
 * - username: 4th link of the ul.linklist.navlinks breadcrumb (personal
 *   albums: Список форумов › Галерея › Личные альбомы › 8008 › Metallica);
 *   common albums have a shorter breadcrumb — username stays null;
 * - images: a.highslide elements; href points to the full image, the rel
 *   attribute contains an image_page.php link with image_id, the title
 *   attribute holds the image name;
 * - pagination: div.pagination with album.php?album_id=...&start=N links
 *   (100 images per page).
 */
final class GalleryAlbumPageParser
{
    /**
     * @return array{album: AlbumData, images: GalleryImageData[]}
     */
    public function parse(int $albumId, string $sourceUrl, string $html): array
    {
        $xpath = $this->loadXPath($html);

        $bodyNodes = $xpath->query('//body');
        $bodyNode = $bodyNodes === false ? null : $bodyNodes->item(0);
        $body = $bodyNode === null ? '' : $this->cleanText($bodyNode->textContent);
        if (str_contains($body, 'вы должны быть авторизованы')) {
            throw new ForumLoginRequiredException('Album is available to authorized users only.');
        }
        if (str_contains($body, 'Запрошенный альбом не существует')) {
            throw new ForumPageNotFoundException('Album does not exist: ' . $sourceUrl);
        }

        $title = $this->cleanText($this->firstNodeText(
            $xpath,
            '//div[contains(concat(" ", normalize-space(@id), " "), " page-body ")]'
            . '/h2/a[contains(@href, "album.php")]|//div[contains(concat(" ", normalize-space(@id), " "), " page-body ")]/h2'
        ));
        if ($title === '') {
            throw new RuntimeException('Album title was not found.');
        }

        return [
            'album' => new AlbumData($albumId, $sourceUrl, $title, $this->parseUsername($xpath)),
            'images' => $this->parseImages($xpath, $albumId, $sourceUrl),
        ];
    }

    /**
     * Extracts the next page url from the div.pagination block.
     * The gallery lists page links as album.php?album_id=...&start=N; the
     * next page is the link with the smallest start offset greater than
     * the current page offset.
     */
    public function parseNextPageUrl(string $pageUrl, string $html): ?string
    {
        $xpath = $this->loadXPath($html);

        parse_str((string)parse_url($pageUrl, PHP_URL_QUERY), $currentQuery);
        $currentStart = (int)($currentQuery['start'] ?? 0);
        $albumId = (int)($currentQuery['album_id'] ?? 0);

        $nextStart = null;
        /** @var DOMElement $link */
        foreach (
            $xpath->query(
                '//div[contains(concat(" ", normalize-space(@class), " "), " pagination ")]'
                . '//a[contains(@href, "album.php")][contains(@href, "start=")]'
            ) ?: [] as $link
        ) {
            parse_str((string)parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
            if (!isset($query['start'])) {
                continue;
            }
            $start = (int)$query['start'];
            if ($start > $currentStart && ($nextStart === null || $start < $nextStart)) {
                $nextStart = $start;
            }
        }
        if ($nextStart === null || $albumId <= 0) {
            return null;
        }
        return $this->absoluteUrl('album.php?album_id=' . $albumId . '&start=' . $nextStart, $pageUrl);
    }

    private function loadXPath(string $html): DOMXPath
    {
        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        if (!$loaded || $document->documentElement === null) {
            throw new RuntimeException('Album page HTML is not loadable.');
        }
        return new DOMXPath($document);
    }

    /**
     * The username of a personal album is the 4th breadcrumb link
     * (Список форумов › Галерея › Личные альбомы › 8008 › Metallica),
     * recognized by the 3rd link pointing to the personal gallery list.
     * Common albums have a different breadcrumb — null is returned.
     */
    private function parseUsername(DOMXPath $xpath): ?string
    {
        $links = $xpath->query(
            '//ul[contains(concat(" ", normalize-space(@class), " "), " navlinks ")]'
            . '/li[not(contains(concat(" ", normalize-space(@class), " "), " rightside "))]/a'
        );
        if ($links === false || $links->length < 4) {
            return null;
        }
        $personal = $links->item(2);
        if (!$personal instanceof DOMElement || !str_contains($personal->getAttribute('href'), 'mode=personal')) {
            return null;
        }
        $usernameLink = $links->item(3);
        if (!$usernameLink instanceof DOMElement) {
            return null;
        }
        $username = $this->cleanText($usernameLink->textContent);
        return $username === '' ? null : $username;
    }

    /**
     * @return GalleryImageData[]
     */
    private function parseImages(DOMXPath $xpath, int $albumId, string $sourceUrl): array
    {
        $images = [];
        $seen = [];
        /** @var DOMElement $link */
        foreach ($xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " highslide ")]') ?: [] as $link) {
            $imageUrl = $this->absoluteUrl($link->getAttribute('href'), $sourceUrl);
            if (!preg_match('~\.(?:gif|jpe?g|png|webp)(?:[?#]|$)~ui', $imageUrl)) {
                continue;
            }
            $title = $this->cleanText($link->getAttribute('title'));
            $imageId = 0;
            // rel holds an escaped image_page.php link: image_id=2048754
            $rel = html_entity_decode($link->getAttribute('rel'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (preg_match('~image_id=(\d+)~u', $rel, $m) === 1) {
                $imageId = (int)$m[1];
            }
            if ($imageId <= 0 || isset($seen[$imageId])) {
                continue;
            }
            $seen[$imageId] = true;
            $images[] = new GalleryImageData(
                $imageId,
                $albumId,
                $title,
                $imageUrl,
                $this->absoluteUrl('image_page.php?album_id=' . $albumId . '&image_id=' . $imageId, $sourceUrl),
            );
        }
        return $images;
    }

    private function firstNodeText(DOMXPath $xpath, string $query): string
    {
        $nodes = $xpath->query($query);
        $node = $nodes === false ? null : $nodes->item(0);
        return $node === null ? '' : $node->textContent;
    }

    private function cleanText(string $text): string
    {
        return trim((string)preg_replace('/[\s\x{00A0}]+/u', ' ', $text));
    }

    /**
     * Turns relative gallery hrefs into absolute ones and drops the phpBB sid parameter.
     */
    private function absoluteUrl(string $url, string $base): string
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
            } elseif (str_starts_with($url, '../')) {
                // Gallery links are relative to the /gallery/ directory:
                // base .../gallery/album.php + ../gallery/images/...
                $path = preg_replace('~/[^/]*$~u', '', $parts['path'] ?? '') ?: '';
                $absolute = $origin . $this->resolveRelativePath($path, $url);
            } else {
                $path = preg_replace('~/[^/]*$~u', '', $parts['path'] ?? '') ?: '';
                $absolute = $origin . $path . '/' . ltrim($url, './');
            }
        }
        return $this->stripSessionId($absolute);
    }

    /**
     * Resolves ../ sequences: /gallery/album.php + ../gallery/images/upload/...
     * -> /gallery/images/upload/...
     */
    private function resolveRelativePath(string $basePath, string $relative): string
    {
        $result = explode('/', trim($basePath, '/'));
        foreach (explode('/', $relative) as $segment) {
            if ($segment === '..') {
                array_pop($result);
            } elseif ($segment !== '' && $segment !== '.') {
                $result[] = $segment;
            }
        }
        return '/' . implode('/', $result);
    }

    /**
     * Drops the phpBB session id parameter from stored URLs.
     */
    private function stripSessionId(string $url): string
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
        $result = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
        if (isset($parts['port'])) {
            $result .= ':' . $parts['port'];
        }
        $result .= $parts['path'] ?? '';
        if (isset($parts['query'])) {
            $result .= '?' . $parts['query'];
        }
        if (isset($parts['fragment'])) {
            $result .= '#' . $parts['fragment'];
        }
        return $result;
    }
}
