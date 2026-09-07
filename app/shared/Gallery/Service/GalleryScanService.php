<?php

declare(strict_types=1);

namespace app\shared\Gallery\Service;

use app\shared\Forum\Contract\ForumHttpClientInterface;
use app\shared\Forum\Infrastructure\ForumLoginRequiredException;
use app\shared\Forum\Infrastructure\ForumPageNotFoundException;
use app\shared\Gallery\Contract\GalleryRepositoryInterface;
use app\shared\Gallery\Dto\AlbumData;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\LoggerInterface;

/**
 * Iterates the configured album id range, walks every page of each album
 * through the gallery pagination and upserts the album with all its
 * images. A failure of a single album never stops the run.
 */
final class GalleryScanService
{
    private const MAX_PAGES_PER_ALBUM = 10000;

    public function __construct(
        private readonly GalleryRepositoryInterface $repository,
        private readonly ForumHttpClientInterface $httpClient,
        private readonly GalleryAlbumPageParser $parser,
        private readonly LoggerInterface $logger,
        private readonly string $configCode = 'awd_gallery_albums',
    ) {
    }

    /**
     * @param int|null $from explicit range start override
     * @param int|null $to explicit range end override
     * @param int|null $limit max number of album ids to process in this run
     * @param int|null $pageLimit max number of pages to fetch per album in this run
     * @return array{processed: int, saved: int, updated: int, not_found: int, login_required: int, images_saved: int, images_updated: int, failed: int}|null null when another scan is already running
     */
    public function run(?int $from = null, ?int $to = null, ?int $limit = null, ?int $pageLimit = null): ?array
    {
        if (!$this->repository->acquireLock($this->configCode)) {
            $this->logger->warning('Gallery scan is already running, launch skipped.', ['code' => $this->configCode]);
            return null;
        }

        try {
            return $this->doRun($from, $to, $limit, $pageLimit);
        } finally {
            $this->repository->releaseLock($this->configCode);
        }
    }

    /**
     * @return array{processed: int, saved: int, updated: int, not_found: int, login_required: int, images_saved: int, images_updated: int, failed: int}
     */
    private function doRun(?int $from, ?int $to, ?int $limit, ?int $pageLimit): array
    {
        $config = $this->repository->activeConfig($this->configCode);
        if ($config === null) {
            $this->logger->warning('Parser config is missing or disabled.', ['code' => $this->configCode]);
            return $this->emptyStats();
        }

        $start = max((int)$config['t_from'], $from ?? (int)$config['t_from']);
        $end = min((int)$config['t_to'], $to ?? (int)$config['t_to']);
        $stats = $this->emptyStats();

        for ($id = $start; $id <= $end && ($limit === null || $stats['processed'] < $limit); $id++) {
            $stats['processed']++;
            $albumStats = $this->scanAlbum((int)$id, rtrim((string)$config['base_url'], '=') . '=' . $id, $pageLimit);
            foreach ($albumStats as $key => $value) {
                $stats[$key] += $value;
            }
        }

        $endNow = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->repository->markRun($this->configCode, $endNow->format('Y-m-d H:i:s'));
        $this->logger->info('Gallery scan finished.', array_merge(['code' => $this->configCode], $stats));
        return $stats;
    }

    /**
     * @return array{saved: int, updated: int, not_found: int, login_required: int, images_saved: int, images_updated: int, failed: int}
     */
    private function scanAlbum(int $albumId, string $albumUrl, ?int $pageLimit): array
    {
        $stats = [
            'saved' => 0,
            'updated' => 0,
            'not_found' => 0,
            'login_required' => 0,
            'images_saved' => 0,
            'images_updated' => 0,
            'failed' => 0,
        ];
        $pages = 0;
        $pageUrl = $albumUrl;
        $album = null;
        $images = [];
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        try {
            while ($pageUrl !== null) {
                if ($pages >= self::MAX_PAGES_PER_ALBUM || ($pageLimit !== null && $pages >= $pageLimit)) {
                    break;
                }
                // A fresh timestamp per album: updated_at must show when the
                // row was actually refreshed, not when the run started.
                $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                $html = $this->httpClient->get($pageUrl);
                ['album' => $parsedAlbum, 'images' => $pageImages] = $this->parser->parse($albumId, $albumUrl, $html);
                if ($album === null) {
                    $album = $parsedAlbum;
                }
                foreach ($pageImages as $image) {
                    $images[$image->id] = $image;
                }
                $pages++;
                $pageUrl = $this->parser->parseNextPageUrl($pageUrl, $html);
            }

            if ($album !== null) {
                $nowSql = $now->format('Y-m-d H:i:s');
                ['album_inserted' => $albumInserted, 'images_inserted' => $imagesInserted] =
                    $this->repository->save($album, array_values($images), $nowSql);
                $albumInserted ? $stats['saved']++ : $stats['updated']++;
                $stats['images_saved'] += $imagesInserted;
                $stats['images_updated'] += count($images) - $imagesInserted;
            }
        } catch (ForumPageNotFoundException $e) {
            $stats['not_found']++;
            $this->logger->info($e->getMessage(), ['album_id' => $albumId]);
        } catch (ForumLoginRequiredException $e) {
            $stats['login_required']++;
            $this->logger->info($e->getMessage(), ['album_id' => $albumId]);
            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            ['album_inserted' => $albumInserted] = $this->repository->save(
                AlbumData::loginRequired($albumId, $albumUrl),
                [],
                $now->format('Y-m-d H:i:s')
            );
            $albumInserted ? $stats['saved']++ : $stats['updated']++;
        } catch (\Throwable $e) {
            $stats['failed']++;
            $this->logger->warning('Album skipped.', ['album_id' => $albumId, 'error' => $e->getMessage()]);
        }
        return $stats;
    }

    /**
     * @return array{processed: int, saved: int, updated: int, not_found: int, login_required: int, images_saved: int, images_updated: int, failed: int}
     */
    private function emptyStats(): array
    {
        return [
            'processed' => 0,
            'saved' => 0,
            'updated' => 0,
            'not_found' => 0,
            'login_required' => 0,
            'images_saved' => 0,
            'images_updated' => 0,
            'failed' => 0,
        ];
    }
}
