<?php

declare(strict_types=1);

namespace app\shared\Forum\Contract;

/**
 * Storage boundary for the forum parser.
 */
interface ForumRepositoryInterface extends ForumPublicationMapGatewayInterface
{
    public function activeConfig(string $code): ?array;

    public function markRun(string $code, string $time): void;

    /**
     * Tries to acquire an exclusive run lock for the config.
     * Returns false when another scan process is already running.
     */
    public function acquireLock(string $code): bool;

    public function releaseLock(string $code): void;

    /**
     * Upserts the topic and its author. Returns true when a new row was inserted.
     */
    public function save(\app\shared\Forum\Dto\TopicData $topic, string $now): bool;

    /**
     * Upserts the posts of one topic page together with their authors.
     * Returns the number of newly inserted post rows.
     */
    public function savePosts(array $posts, string $now): int;

    /**
     * Ids of topics that exist in the topic table and are not login-required stubs.
     *
     * @return int[]
     */
    public function existingTopicIds(int $fromId, int $toId): array;

    /**
     * Upserts a member profile parsed from the memberlist pages.
     * Returns true when a new row was inserted.
     */
    public function saveMemberProfile(\app\shared\Forum\Dto\MemberData $member, string $now): bool;

    /**
     * Latest accessible topics (login_required = false) sorted by
     * published_at descending, at most $topicLimit rows, each with its
     * own latest posts (at most $postLimit, posted_at descending).
     * With $withImagesOnly = true only topics/posts having non-empty
     * image_urls are returned. With $withPostsOnly = true only topics
     * having at least one post are returned, and every post the shown
     * topic has is returned along with it, processed ones included;
     * otherwise the post lists keep only the unprocessed ones. With
     * $imagesCount > 0 only topics/posts having exactly $imagesCount
     * images are returned.
     *
     * @return array<int, array{topic: \app\shared\Forum\Dto\TopicData, posts: \app\shared\Forum\Dto\PostData[]}>
     */
    public function latestTopicsWithPosts(int $topicLimit, int $postLimit, bool $withImagesOnly = false, bool $withPostsOnly = false, int $imagesCount = 0): array;

    /**
     * Marks a forum topic as viewed by the "Просмотрено" button:
     * inserts a publications_topic_map row with an empty telegram_id
     * (an existing row is left untouched). The viewed element is not
     * published to the channel by itself, so the telegram_id stays
     * empty unless a publication created from it is later sent to
     * the channel.
     */
    public function markTopicViewed(int $topicId): void;

    /**
     * Marks a forum post as viewed by the "Просмотрено" button:
     * inserts a publications_post_map row with an empty telegram_id
     * (an existing row is left untouched).
     */
    public function markPostViewed(int $postId): void;
}
