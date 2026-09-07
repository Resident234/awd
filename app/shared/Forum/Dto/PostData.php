<?php

declare(strict_types=1);

namespace app\shared\Forum\Dto;

/**
 * A single forum post extracted from a topic page.
 * id = the p parameter of the post (e.g. p11861699 -> 11861699).
 */
final readonly class PostData
{
    public function __construct(
        public int $id,
        public int $topicId,
        public ?int $authorId,
        public ?int $number,
        public string $title,
        public ?string $postedAt,
        public string $contentHtml,
        public string $contentText,
        public string $sourceUrl,
        public ?MemberData $author = null,
    ) {
    }
}
