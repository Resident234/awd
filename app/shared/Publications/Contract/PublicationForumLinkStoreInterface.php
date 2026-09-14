<?php

declare(strict_types=1);

namespace app\shared\Publications\Contract;

use app\shared\Publications\Dto\ForumPublicationRef;

/**
 * Temporary storage of the "forum entity - publication" link: the
 * link is remembered when the publication form (filled from a forum
 * topic or post) is saved as a draft or a scheduled post, follows the
 * record when it moves between the publication tables, and is
 * consumed when the record is actually sent to the channel.
 */
interface PublicationForumLinkStoreInterface
{
    public function remember(int $publicationId, ForumPublicationRef $ref): void;

    public function find(int $publicationId): ?ForumPublicationRef;

    /**
     * Moves the link to another publication id (used when a record
     * changes its id while moving between tables).
     */
    public function move(int $fromPublicationId, int $toPublicationId): void;

    public function forget(int $publicationId): void;
}
