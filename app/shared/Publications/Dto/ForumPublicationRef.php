<?php

declare(strict_types=1);

namespace app\shared\Publications\Dto;

/**
 * A reference to the forum entity a publication was created from:
 * the entity type ("topic" or "post") and its id. Kept in a temporary
 * link store until the publication reaches the Telegram channel, at
 * which point the telegram_id is written into the map table row.
 */
final readonly class ForumPublicationRef
{
    public function __construct(
        public string $type,
        public int $id,
    ) {
    }

    public function isTopic(): bool
    {
        return $this->type === 'topic';
    }

    public function isPost(): bool
    {
        return $this->type === 'post';
    }
}
