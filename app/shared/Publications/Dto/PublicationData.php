<?php

declare(strict_types=1);

namespace app\shared\Publications\Dto;

/**
 * A channel publication: a scheduled or published post, or a draft.
 * telegramId is null until the post is actually sent to Telegram.
 */
final readonly class PublicationData
{
    /**
     * @param string[] $imageUrls
     */
    public function __construct(
        public int $id,
        public string $text,
        public array $imageUrls,
        public ?int $telegramId,
        public ?string $publishedAt,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
