<?php

declare(strict_types=1);

namespace app\shared\Publications\Contract;

use app\shared\Publications\Dto\PublicationData;

/**
 * Storage boundary for channel publications: scheduled posts and drafts.
 */
interface PublicationRepositoryInterface
{
    /**
     * @return PublicationData[] scheduled and published posts,
     * sorted by published_at descending
     */
    public function allPosts(): array;

    /**
     * @return PublicationData[] drafts, sorted by updated_at descending
     */
    public function allDrafts(): array;

    /**
     * Creates a draft from the form data.
     */
    public function createDraft(string $text, array $imageUrls, string $now): void;

    /**
     * Creates a scheduled (or immediately due) post from the form data.
     */
    public function createPost(string $text, array $imageUrls, string $publishedAt, string $now): void;

    /**
     * @return PublicationData[] posts with an empty telegram_id and a due
     * publication time, ordered from the smallest id to the biggest one
     */
    public function findDueForPublishing(string $now): array;

    /**
     * Stores the Telegram message id of a successfully published post
     * and corrects published_at to the actual send time.
     */
    public function storeTelegramId(int $id, int $telegramId, string $publishedAt, string $now): void;

    /**
     * Finds a post by id.
     */
    public function findPost(int $id): ?PublicationData;

    /**
     * Finds a draft by id.
     */
    public function findDraft(int $id): ?PublicationData;

    /**
     * Updates a scheduled post: text, image urls and published_at from
     * the form; updated_at is set to $now, created_at stays untouched.
     */
    public function updatePost(int $id, string $text, array $imageUrls, string $publishedAt, string $now): void;

    /**
     * Updates a draft: text and image urls from the form;
     * updated_at is set to $now, created_at stays untouched.
     */
    public function updateDraft(int $id, string $text, array $imageUrls, string $now): void;

    /**
     * Deletes a post row and returns it as it was before deletion.
     */
    public function deletePost(int $id): PublicationData;

    /**
     * Deletes a draft row and returns it as it was before deletion.
     */
    public function deleteDraft(int $id): PublicationData;

    /**
     * Inserts a post row preserving the original created_at (used when
     * moving records between the posts, drafts and edited tables).
     */
    public function insertPostWithHistory(PublicationData $post, string $now): void;

    /**
     * Inserts a draft row preserving the original created_at.
     */
    public function insertDraftWithHistory(PublicationData $draft, string $now): void;

    /**
     * Archives a published post row into publications_edited with all
     * field values copied; updated_at is set to $now.
     */
    public function archiveEdited(PublicationData $post, string $now): void;

    /**
     * Inserts a publications_deleted row preserving the original
     * created_at and published_at; updated_at is set to $now,
     * deleted_at stays empty until the periodic task confirms the
     * channel removal.
     */
    public function insertDeletedWithHistory(PublicationData $record, string $now): void;

    /**
     * @return PublicationData[] soft-deleted posts with an empty
     * deleted_at, i.e. records awaiting removal from the channel,
     * ordered from the smallest id to the biggest one
     */
    public function findPendingChannelDeletion(): array;

    /**
     * Stamps the time a soft-deleted record was actually removed
     * from the channel.
     */
    public function storeDeletedAt(int $id, string $deletedAt): void;

    /**
     * @return PublicationData[] archived edited posts with an empty
     * edited_at, i.e. records awaiting the channel update, ordered
     * from the smallest id to the biggest one
     */
    public function findPendingChannelEdits(): array;

    /**
     * Stamps the time an edited record's message was actually
     * updated in the channel.
     */
    public function storeEditedAt(int $id, string $editedAt): void;
}
