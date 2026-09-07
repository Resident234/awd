<?php

declare(strict_types=1);

namespace app\shared\Gallery\Dto;

/**
 * A single image extracted from an album page.
 * id = the image_id parameter of the image page link (e.g. 2048754).
 */
final readonly class GalleryImageData
{
    public function __construct(
        public int $id,
        public int $albumId,
        public string $title,
        public string $imageUrl,
        public string $sourceUrl,
    ) {
    }
}
