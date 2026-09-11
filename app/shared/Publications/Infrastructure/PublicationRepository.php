<?php

declare(strict_types=1);

namespace app\shared\Publications\Infrastructure;

use app\shared\Publications\Contract\PublicationRepositoryInterface;
use app\shared\Publications\Dto\PublicationData;
use InvalidArgumentException;
use PDO;
use yii\db\Connection;

/**
 * PostgreSQL storage for channel publications. All SQL lives here:
 * upper layers receive and return DTOs only.
 */
final class PublicationRepository implements PublicationRepositoryInterface
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function allPosts(): array
    {
        return $this->hydrateAll(
            'SELECT id, text, image_urls, telegram_id, published_at, created_at, updated_at'
            . ' FROM {{%publications_post}} ORDER BY published_at DESC, id DESC',
        );
    }

    public function allDrafts(): array
    {
        return $this->hydrateAll(
            'SELECT id, text, image_urls, NULL AS telegram_id, NULL AS published_at, created_at, updated_at'
            . ' FROM {{%publications_draft}} ORDER BY updated_at DESC, id DESC',
        );
    }

    public function createDraft(string $text, array $imageUrls, string $now): void
    {
        $this->db
            ->createCommand()
            ->insert('{{%publications_draft}}', [
                'text' => $text,
                'image_urls' => $imageUrls,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->execute();
    }

    public function createPost(string $text, array $imageUrls, string $publishedAt, string $now): void
    {
        $this->db
            ->createCommand()
            ->insert('{{%publications_post}}', [
                'text' => $text,
                'image_urls' => $imageUrls,
                'published_at' => $publishedAt,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->execute();
    }

    public function findDueForPublishing(string $now): array
    {
        return $this->hydrateAll(
            'SELECT id, text, image_urls, telegram_id, published_at, created_at, updated_at'
            . ' FROM {{%publications_post}}'
            . ' WHERE telegram_id IS NULL AND published_at <= :now'
            . ' ORDER BY id ASC',
            [':now' => $now],
        );
    }

    public function storeTelegramId(int $id, int $telegramId, string $publishedAt, string $now): void
    {
        $this->db
            ->createCommand()
            ->update('{{%publications_post}}', [
                'telegram_id' => $telegramId,
                'published_at' => $publishedAt,
                'updated_at' => $now,
            ], ['id' => $id])
            ->execute();
    }

    public function findPost(int $id): ?PublicationData
    {
        return $this->hydrateOne(
            'SELECT id, text, image_urls, telegram_id, published_at, created_at, updated_at'
            . ' FROM {{%publications_post}} WHERE id = :id',
            [':id' => $id],
        );
    }

    public function findDraft(int $id): ?PublicationData
    {
        return $this->hydrateOne(
            'SELECT id, text, image_urls, NULL AS telegram_id, NULL AS published_at, created_at, updated_at'
            . ' FROM {{%publications_draft}} WHERE id = :id',
            [':id' => $id],
        );
    }

    public function updatePost(int $id, string $text, array $imageUrls, string $publishedAt, string $now): void
    {
        $this->db
            ->createCommand()
            ->update('{{%publications_post}}', [
                'text' => $text,
                'image_urls' => $imageUrls,
                'published_at' => $publishedAt,
                'updated_at' => $now,
            ], ['id' => $id])
            ->execute();
    }

    public function updateDraft(int $id, string $text, array $imageUrls, string $now): void
    {
        $this->db
            ->createCommand()
            ->update('{{%publications_draft}}', [
                'text' => $text,
                'image_urls' => $imageUrls,
                'updated_at' => $now,
            ], ['id' => $id])
            ->execute();
    }

    public function deletePost(int $id): PublicationData
    {
        $post = $this->findPost($id);
        if ($post === null) {
            throw new InvalidArgumentException("Публикация #{$id} не найдена.");
        }

        $this->db
            ->createCommand()
            ->delete('{{%publications_post}}', ['id' => $id])
            ->execute();

        return $post;
    }

    public function deleteDraft(int $id): PublicationData
    {
        $draft = $this->findDraft($id);
        if ($draft === null) {
            throw new InvalidArgumentException("Черновик #{$id} не найден.");
        }

        $this->db
            ->createCommand()
            ->delete('{{%publications_draft}}', ['id' => $id])
            ->execute();

        return $draft;
    }

    public function insertPostWithHistory(PublicationData $post, string $now): void
    {
        $this->db
            ->createCommand()
            ->insert('{{%publications_post}}', [
                'telegram_id' => $post->telegramId,
                'text' => $post->text,
                'image_urls' => $post->imageUrls,
                'published_at' => $post->publishedAt ?? $now,
                'created_at' => $post->createdAt,
                'updated_at' => $now,
            ])
            ->execute();
    }

    public function insertDraftWithHistory(PublicationData $draft, string $now): void
    {
        $this->db
            ->createCommand()
            ->insert('{{%publications_draft}}', [
                'text' => $draft->text,
                'image_urls' => $draft->imageUrls,
                'created_at' => $draft->createdAt,
                'updated_at' => $now,
            ])
            ->execute();
    }

    public function archiveEdited(PublicationData $post, string $now): void
    {
        $this->db
            ->createCommand()
            ->insert('{{%publications_edited}}', [
                'telegram_id' => $post->telegramId,
                'text' => $post->text,
                'image_urls' => $post->imageUrls,
                'published_at' => $post->publishedAt ?? $now,
                'created_at' => $post->createdAt,
                'updated_at' => $now,
                'edited_at' => null,
            ])
            ->execute();
    }

    /**
     * @param array<string, int|string> $params
     */
    private function hydrateOne(string $sql, array $params = []): ?PublicationData
    {
        $row = $this->db
            ->createCommand($sql)
            ->bindValues($params)
            ->queryOne(PDO::FETCH_ASSOC);

        if ($row === false || $row === []) {
            return null;
        }

        return new PublicationData(
            (int)$row['id'],
            (string)$row['text'],
            self::decodeImageUrls($row['image_urls']),
            $row['telegram_id'] === null ? null : (int)$row['telegram_id'],
            $row['published_at'] === null ? null : (string)$row['published_at'],
            (string)$row['created_at'],
            (string)$row['updated_at'],
        );
    }

    /**
     * @param array<string, string> $params
     * @return PublicationData[]
     */
    private function hydrateAll(string $sql, array $params = []): array
    {
        $rows = $this->db
            ->createCommand($sql)
            ->bindValues($params)
            ->queryAll(PDO::FETCH_ASSOC);

        return array_map(
            static fn (array $row): PublicationData => new PublicationData(
                (int)$row['id'],
                (string)$row['text'],
                self::decodeImageUrls($row['image_urls']),
                $row['telegram_id'] === null ? null : (int)$row['telegram_id'],
                $row['published_at'] === null ? null : (string)$row['published_at'],
                (string)$row['created_at'],
                (string)$row['updated_at'],
            ),
            $rows,
        );
    }

    /**
     * jsonb columns arrive as JSON strings; the Yii pgsql schema
     * encodes array parameters on write, so arrays pass through.
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
