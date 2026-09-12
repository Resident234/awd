<?php

declare(strict_types=1);

namespace app\shared\Forum\Infrastructure;

use app\shared\Forum\Contract\ForumRepositoryInterface;
use app\shared\Forum\Dto\MemberData;
use app\shared\Forum\Dto\PostData;
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

    /**
     * Upserts the posts of one topic page together with their authors
     * in a single transaction. Returns the number of newly inserted posts.
     */
    public function savePosts(array $posts, string $now): int
    {
        $inserted = 0;
        $transaction = $this->db->beginTransaction();
        try {
            foreach ($posts as $post) {
                if (!$post instanceof PostData) {
                    continue;
                }
                if ($post->author !== null) {
                    $this->saveMember($post->author, $now);
                }
                $exists = $this->db
                    ->createCommand('SELECT 1 FROM {{%post}} WHERE id = :id')
                    ->bindValue(':id', $post->id)
                    ->queryScalar() !== false;
                $this->savePost($post, $now);
                if (!$exists) {
                    $inserted++;
                }
            }
            $transaction->commit();
            return $inserted;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * @return int[]
     */
    public function existingTopicIds(int $fromId, int $toId): array
    {
        $rows = $this->db
            ->createCommand(
                'SELECT id FROM {{%topic}} WHERE id BETWEEN :from AND :to AND login_required = FALSE ORDER BY id'
            )
            ->bindValues([':from' => $fromId, ':to' => $toId])
            ->queryColumn();
        return array_map(intval(...), $rows);
    }

    private function savePost(PostData $post, string $now): void
    {
        $this->db->createCommand()->upsert(
            '{{%post}}',
            [
                'id' => $post->id,
                'topic_id' => $post->topicId,
                'author_id' => $post->author?->id,
                'number' => $post->number,
                'title' => $post->title,
                'posted_at' => $post->postedAt,
                'content_html' => $post->contentHtml,
                'content_text' => $post->contentText,
                'source_url' => $post->sourceUrl,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'topic_id' => $post->topicId,
                'author_id' => $post->author?->id,
                'number' => $post->number,
                'title' => $post->title,
                'posted_at' => $post->postedAt,
                'content_html' => $post->contentHtml,
                'content_text' => $post->contentText,
                'source_url' => $post->sourceUrl,
                'updated_at' => $now,
            ]
        )->execute();
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
                'login_required' => $topic->loginRequired,
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
                'login_required' => $topic->loginRequired,
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
                'last_visit_at' => $member->lastVisitAt,
                'photos_count' => $member->photosCount,
                'city' => $member->city,
                'thanks_given_count' => $member->thanksGivenCount,
                'thanks_received_count' => $member->thanksReceivedCount,
                'age' => $member->age,
                'countries_count' => $member->countriesCount,
                'reports_count' => $member->reportsCount,
                'gender' => $member->gender,
                'profile_login_required' => $member->profileLoginRequired,
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
                'last_visit_at' => $member->lastVisitAt,
                'photos_count' => $member->photosCount,
                'city' => $member->city,
                'thanks_given_count' => $member->thanksGivenCount,
                'thanks_received_count' => $member->thanksReceivedCount,
                'age' => $member->age,
                'countries_count' => $member->countriesCount,
                'reports_count' => $member->reportsCount,
                'gender' => $member->gender,
                'profile_login_required' => $member->profileLoginRequired,
                'raw_data' => new JsonExpression($member->rawData),
                'updated_at' => $now,
            ]
        )->execute();
    }

    /**
     * Upserts a member profile parsed from the memberlist pages. Unlike
     * saveMember() (which overwrites everything with post-block values),
     * this merges: fields the memberlist does not provide (thanks, countries,
     * reports) keep their stored values. Returns true for a new row.
     */
    public function saveMemberProfile(MemberData $member, string $now): bool
    {
        $exists = $this->db
            ->createCommand('SELECT 1 FROM {{%member}} WHERE id = :id')
            ->bindValue(':id', $member->id)
            ->queryScalar() !== false;

        $values = [
            'profile_url' => $member->profileUrl,
            'name' => $member->name,
            'avatar_url' => $member->avatarUrl,
            'rank_name' => $member->rankName,
            'messages_count' => $member->messagesCount,
            'registered_on' => $member->registeredOn,
            'last_visit_at' => $member->lastVisitAt,
            'photos_count' => $member->photosCount,
            'city' => $member->city,
            'age' => $member->age,
            'gender' => $member->gender,
            'profile_login_required' => $member->profileLoginRequired,
            'updated_at' => $now,
        ];
        $this->db->createCommand()->upsert(
            '{{%member}}',
            array_merge($values, [
                'id' => $member->id,
                'raw_data' => new JsonExpression($member->rawData),
                'created_at' => $now,
            ]),
            array_merge($values, ['raw_data' => new JsonExpression($member->rawData)])
        )->execute();
        return !$exists;
    }

    /**
     * Latest accessible topics (login_required = false) sorted by
     * published_at descending, at most $topicLimit rows. Each topic
     * carries its own posts (at most $postLimit, posted_at descending,
     * full field values) with author data hydrated from the joined
     * member rows.
     *
     * @return array<int, array{topic: TopicData, posts: PostData[]}>
     */
    public function latestTopicsWithPosts(int $topicLimit, int $postLimit): array
    {
        $topicRows = $this->db
            ->createCommand(
                'SELECT t.id, t.source_url, t.title, t.published_at, t.content_html, t.content_text, t.image_urls,'
                . ' m.id AS author_id, m.profile_url AS author_profile_url, m.name AS author_name,'
                . ' m.avatar_url AS author_avatar_url, m.rank_name AS author_rank_name'
                . ' FROM {{%topic}} t'
                . ' LEFT JOIN {{%member}} m ON m.id = t.author_id'
                . ' WHERE t.login_required = FALSE'
                . ' ORDER BY t.published_at DESC NULLS LAST, t.id DESC'
                . ' LIMIT :limit'
            )
            ->bindValue(':limit', $topicLimit)
            ->queryAll(PDO::FETCH_ASSOC);

        if ($topicRows === []) {
            return [];
        }

        $topicIds = array_map(static fn (array $row): int => (int)$row['id'], $topicRows);
        $postRows = $this->db
            ->createCommand(
                'SELECT p.id, p.topic_id, p.author_id, p.number, p.title, p.posted_at, p.content_html, p.content_text, p.source_url,'
                . ' m.profile_url AS author_profile_url, m.name AS author_name,'
                . ' m.avatar_url AS author_avatar_url, m.rank_name AS author_rank_name'
                . ' FROM ('
                . ' SELECT id, topic_id, author_id, number, title, posted_at, content_html, content_text, source_url,'
                . ' ROW_NUMBER() OVER (PARTITION BY topic_id ORDER BY posted_at DESC NULLS LAST, id DESC) AS rn'
                . ' FROM {{%post}}'
                . ' WHERE topic_id IN (' . implode(',', $topicIds) . ')'
                . ' ) p'
                . ' LEFT JOIN {{%member}} m ON m.id = p.author_id'
                . ' WHERE p.rn <= :postLimit'
                . ' ORDER BY p.topic_id, p.posted_at DESC NULLS LAST, p.id DESC'
            )
            ->bindValue(':postLimit', $postLimit)
            ->queryAll(PDO::FETCH_ASSOC);

        $postsByTopic = [];
        foreach ($postRows as $row) {
            $author = $row['author_id'] === null ? null : new MemberData(
                (int)$row['author_id'],
                (string)($row['author_profile_url'] ?? ''),
                (string)($row['author_name'] ?? ''),
                $row['author_avatar_url'] === null ? null : (string)$row['author_avatar_url'],
                $row['author_rank_name'] === null ? null : (string)$row['author_rank_name'],
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                [],
            );
            $postsByTopic[(int)$row['topic_id']][] = new PostData(
                (int)$row['id'],
                (int)$row['topic_id'],
                $row['author_id'] === null ? null : (int)$row['author_id'],
                $row['number'] === null ? null : (int)$row['number'],
                (string)$row['title'],
                $row['posted_at'] === null ? null : (string)$row['posted_at'],
                (string)$row['content_html'],
                (string)$row['content_text'],
                (string)$row['source_url'],
                $author,
            );
        }

        $result = [];
        foreach ($topicRows as $row) {
            $result[] = [
                'topic' => $this->hydrateTopicRow($row),
                'posts' => $postsByTopic[(int)$row['id']] ?? [],
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateTopicRow(array $row): TopicData
    {
        return new TopicData(
            (int)$row['id'],
            (string)$row['source_url'],
            (string)$row['title'],
            $row['published_at'] === null ? null : (string)$row['published_at'],
            (string)$row['content_html'],
            (string)$row['content_text'],
            self::decodeImageUrls($row['image_urls']),
            $this->hydrateAuthorRow($row),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateAuthorRow(array $row): ?MemberData
    {
        if ($row['author_id'] === null) {
            return null;
        }

        return new MemberData(
            (int)$row['author_id'],
            (string)($row['author_profile_url'] ?? ''),
            (string)($row['author_name'] ?? ''),
            $row['author_avatar_url'] === null ? null : (string)$row['author_avatar_url'],
            $row['author_rank_name'] === null ? null : (string)$row['author_rank_name'],
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            [],
        );
    }

    /**
     * jsonb columns arrive as JSON strings; arrays pass through.
     *
     * @return string[]
     */
    private static function decodeImageUrls(mixed $value): array
    {
        if (is_array($value)) {
            return array_map(static fn (mixed $url): string => (string)$url, $value);
        }

        $decoded = json_decode((string)$value, true);

        return is_array($decoded)
            ? array_map(static fn (mixed $url): string => (string)$url, $decoded)
            : [];
    }
}
