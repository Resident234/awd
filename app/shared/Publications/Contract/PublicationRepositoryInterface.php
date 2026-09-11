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
     * @return PublicationData[] scheduled and published posts, newest first
     */
    public function allPosts(): array;

    /**
     * @return PublicationData[] drafts, newest first
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
}
