<?php

declare(strict_types=1);

namespace app\shared\Gallery\Infrastructure;

use app\shared\Gallery\Contract\GalleryRepositoryInterface;
use app\shared\Gallery\Dto\AlbumData;
use app\shared\Gallery\Dto\GalleryImageData;
use PDO;
use yii\db\Connection;

/**
 * PostgreSQL storage for the gallery parser. All SQL lives here:
 * upper layers receive and return DTOs and plain arrays only.
 */
final class GalleryRepository implements GalleryRepositoryInterface
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

    /**
     * Upserts the album and its images in a single transaction.
     * Returns which album row is new and how many image rows were inserted.
     *
     * @return array{album_inserted: bool, images_inserted: int}
     */
    public function save(AlbumData $album, array $images, string $now): array
    {
        $transaction = $this->db->beginTransaction();
        try {
            $albumExists = $this->db
                ->createCommand('SELECT 1 FROM {{%gallery_album}} WHERE id = :id')
                ->bindValue(':id', $album->id)
                ->queryScalar() !== false;
            $imageIds = [];
            foreach ($images as $image) {
                if ($image instanceof GalleryImageData) {
                    $imageIds[] = $image->id;
                }
            }
            $existing = $imageIds === []
                ? []
                : array_map(
                    intval(...),
                    $this->db
                        ->createCommand('SELECT id FROM {{%gallery_image}} WHERE id = ANY(CAST(:ids AS bigint[]))')
                        ->bindValue(':ids', '{' . implode(',', $imageIds) . '}', PDO::PARAM_STR)
                        ->queryColumn(),
                );
            $this->saveAlbum($album, $now);
            $imagesInserted = 0;
            foreach ($images as $image) {
                if (!$image instanceof GalleryImageData) {
                    continue;
                }
                if (!in_array($image->id, $existing, true)) {
                    $imagesInserted++;
                }
                $this->saveImage($image, $now);
            }
            $transaction->commit();
            return ['album_inserted' => !$albumExists, 'images_inserted' => $imagesInserted];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    private function lockKey(string $code): int
    {
        return (int)sprintf('%u', crc32($code));
    }

    private function saveAlbum(AlbumData $album, string $now): void
    {
        $this->db->createCommand()->upsert(
            '{{%gallery_album}}',
            [
                'id' => $album->id,
                'source_url' => $album->sourceUrl,
                'title' => $album->title,
                'username' => $album->username,
                'login_required' => $album->loginRequired,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'source_url' => $album->sourceUrl,
                'title' => $album->title,
                'username' => $album->username,
                'login_required' => $album->loginRequired,
                'updated_at' => $now,
            ]
        )->execute();
    }

    private function saveImage(GalleryImageData $image, string $now): void
    {
        $this->db->createCommand()->upsert(
            '{{%gallery_image}}',
            [
                'id' => $image->id,
                'album_id' => $image->albumId,
                'title' => $image->title,
                'image_url' => $image->imageUrl,
                'source_url' => $image->sourceUrl,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'album_id' => $image->albumId,
                'title' => $image->title,
                'image_url' => $image->imageUrl,
                'source_url' => $image->sourceUrl,
                'updated_at' => $now,
            ]
        )->execute();
    }
}
