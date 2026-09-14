<?php

declare(strict_types=1);

namespace app\shared\Forum\Contract;

/**
 * Write gateway to the forum-to-Telegram map tables, used by the
 * publications flow when a publication created from a forum topic or
 * post reaches the Telegram channel.
 */
interface ForumPublicationMapGatewayInterface
{
    /**
     * Inserts a publications_topic_map row with an empty telegram_id,
     * or stamps the telegram_id of an existing row.
     */
    public function storeTopicMapTelegramId(int $topicId, ?int $telegramId): void;

    /**
     * Inserts a publications_post_map row with an empty telegram_id,
     * or stamps the telegram_id of an existing row.
     */
    public function storePostMapTelegramId(int $postId, ?int $telegramId): void;
}
