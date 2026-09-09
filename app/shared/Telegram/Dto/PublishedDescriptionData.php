<?php

declare(strict_types=1);

namespace app\shared\Telegram\Dto;

/**
 * A published TRVL channel description: the row currently active
 * has publishedTo = null.
 */
final readonly class PublishedDescriptionData
{
    public function __construct(
        public int $id,
        public string $description,
        public string $publishedFrom,
        public ?string $publishedTo,
    ) {
    }
}
