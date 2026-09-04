<?php

declare(strict_types=1);

namespace app\shared\Forum\Infrastructure;

use app\shared\Forum\Contract\ForumRepositoryInterface;
use app\shared\Forum\Dto\MemberData;
use app\shared\Forum\Dto\TopicData;
use PDO;
use yii\db\Connection;
use yii\db\JsonExpression;

/**
 * PostgreSQL storage for the forum parser. All SQL lives here:
 * upper layers receive and return DTOs and plain arrays only.
 */
final class ForumRepository implements ForumRepositoryInterface
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function activeConfig(string $code): ?array
    {
        $row = $this->db
            ->createCommand('SELECT * FROM {{%parser_config}} WHERE code = :code AND is_active = TRUE LIMIT 1')
            ->bindValues([':code' => $code])
            ->queryOne(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function markRun(string $code, string $time): void
    {
        $this->db
            ->createCommand('UPDATE {{%parser_config}} SET last_run_at = :time, updated_at = :time WHERE code = :code')
            ->bindValues([':time' => $time, ':code' => $code])
            ->execute();
    }

    /**
     * Session-level PostgreSQL advisory lock: held until the process
     * finishes or the DB session drops, so a crashed run never blocks
     * the next launch.
     */
    public function acquireLock(string $code): bool
    {
        return (bool)$this->db
            ->createCommand('SELECT pg_try_advisory_lock(:key)')
            ->bindValue(':key', $this->lockKey($code))
            ->queryScalar();
    }

    public function releaseLock(string $code): void
    {
        $this->db
            ->createCommand('SELECT pg_advisory_unlock(:key)')
            ->bindValue(':key', $this->lockKey($code))
            ->execute();
    }

    private function lockKey(string $code): int
    {
        return (int)sprintf('%u', crc32($code));
    }

    /**
     * Upserts the topic and its author. Returns true when a new row was inserted.
     */
    public function save(TopicData $topic, string $now): bool
    {
        $transaction = $this->db->beginTransaction();
        try {
            $exists = $this->db
                ->createCommand('SELECT 1 FROM {{%topic}} WHERE id = :id')
                ->bindValue(':id', $topic->id)
                ->queryScalar() !== false;
            if ($topic->author !== null) {
                $this->saveMember($topic->author, $now);
            }
            $this->saveTopic($topic, $now);
            $transaction->commit();
            return !$exists;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    private function saveTopic(TopicData $topic, string $now): void
    {
        $this->db->createCommand()->upsert(
            '{{%topic}}',
            [
                'id' => $topic->id,
                'source_url' => $topic->sourceUrl,
                'title' => $topic->title,
                'published_at' => $topic->publishedAt,
                'content_html' => $topic->contentHtml,
                'content_text' => $topic->contentText,
                'image_urls' => new JsonExpression($topic->imageUrls),
                'author_id' => $topic->author?->id,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'source_url' => $topic->sourceUrl,
                'title' => $topic->title,
                'published_at' => $topic->publishedAt,
                'content_html' => $topic->contentHtml,
                'content_text' => $topic->contentText,
                'image_urls' => new JsonExpression($topic->imageUrls),
                'author_id' => $topic->author?->id,
                'updated_at' => $now,
            ]
        )->execute();
    }

    private function saveMember(MemberData $member, string $now): void
    {
        $this->db->createCommand()->upsert(
            '{{%member}}',
            [
                'id' => $member->id,
                'profile_url' => $member->profileUrl,
                'name' => $member->name,
                'avatar_url' => $member->avatarUrl,
                'rank_name' => $member->rankName,
                'messages_count' => $member->messagesCount,
                'registered_on' => $member->registeredOn,
                'city' => $member->city,
                'thanks_given_count' => $member->thanksGivenCount,
                'thanks_received_count' => $member->thanksReceivedCount,
                'age' => $member->age,
                'countries_count' => $member->countriesCount,
                'reports_count' => $member->reportsCount,
                'gender' => $member->gender,
                'raw_data' => new JsonExpression($member->rawData),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'profile_url' => $member->profileUrl,
                'name' => $member->name,
                'avatar_url' => $member->avatarUrl,
                'rank_name' => $member->rankName,
                'messages_count' => $member->messagesCount,
                'registered_on' => $member->registeredOn,
                'city' => $member->city,
                'thanks_given_count' => $member->thanksGivenCount,
                'thanks_received_count' => $member->thanksReceivedCount,
                'age' => $member->age,
                'countries_count' => $member->countriesCount,
                'reports_count' => $member->reportsCount,
                'gender' => $member->gender,
                'raw_data' => new JsonExpression($member->rawData),
                'updated_at' => $now,
            ]
        )->execute();
    }
}
