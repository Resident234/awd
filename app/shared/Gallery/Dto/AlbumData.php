<?php

declare(strict_types=1);

namespace app\shared\Gallery\Dto;

/**
 * A gallery album (forum.awd.ru/gallery/album.php?album_id=...).
 * id = the album_id parameter (e.g. 54903).
 * username = the 4th breadcrumb item of personal albums (e.g. "8008"), null for common albums.
 */
final readonly class AlbumData
{
    public function __construct(
        public int $id,
        public string $sourceUrl,
        public string $title,
        public ?string $username,
        public bool $loginRequired = false,
    ) {
    }

    /**
     * Minimal stub for a page hidden behind forum authorization:
     * only the id and the source url are known.
     */
    public static function loginRequired(int $id, string $sourceUrl): self
    {
        return new self($id, $sourceUrl, '', null, true);
    }
}
