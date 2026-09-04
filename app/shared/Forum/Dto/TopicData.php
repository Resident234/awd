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
    ) {
    }
}
