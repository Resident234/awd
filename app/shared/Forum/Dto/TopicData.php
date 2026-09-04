<?php

declare(strict_types=1);

namespace app\shared\Forum\Dto;

final readonly class TopicData
{
    public function __construct(
        public int $id,
        public string $sourceUrl,
        public string $title,
        public ?string $publishedAt,
        public string $contentHtml,
        public string $contentText,
        public array $imageUrls,
        public ?MemberData $author,
        public bool $loginRequired = false,
    ) {
    }

    /**
     * Minimal stub for a page hidden behind forum authorization:
     * only the id and the source url are known.
     */
    public static function loginRequired(int $id, string $sourceUrl): self
    {
        return new self(
            $id,
            $sourceUrl,
            '',
            null,
            '',
            '',
            [],
            null,
            true,
        );
    }
}
