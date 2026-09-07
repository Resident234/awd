<?php

declare(strict_types=1);

namespace app\shared\Gallery\Contract;

use app\shared\Gallery\Dto\AlbumData;

/**
 * Storage boundary for the gallery parser.
 */
interface GalleryRepositoryInterface
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
     * Upserts the album and its images in a single transaction.
     * Returns which album row is new and how many image rows were inserted.
     *
     * @return array{album_inserted: bool, images_inserted: int}
     */
    public function save(AlbumData $album, array $images, string $now): array;
}
