<?php

declare(strict_types=1);

namespace app\shared\Publications\Infrastructure;

use app\shared\Publications\Contract\PublicationForumLinkStoreInterface;
use app\shared\Publications\Dto\ForumPublicationRef;
use yii\caching\CacheInterface;

/**
 * Cache-backed temporary store of the "forum entity - publication"
 * link. The web app (form submit) and the cron container (publishDue)
 * share the same FileCache directory through the common volume, so
 * the link written by the form is visible to the periodic task. The
 * link lives as long as the publication has not reached the channel;
 * entries are never expired explicitly and are dropped together with
 * the cache when it is flushed.
 */
final class CachePublicationForumLinkStore implements PublicationForumLinkStoreInterface
{
    private const KEY_PREFIX = 'publications.forum-link.';

    public function __construct(private readonly CacheInterface $cache)
    {
    }

    public function remember(int $publicationId, ForumPublicationRef $ref): void
    {
        $this->cache->set(self::key($publicationId), $ref);
    }

    public function find(int $publicationId): ?ForumPublicationRef
    {
        $value = $this->cache->get(self::key($publicationId));

        return $value instanceof ForumPublicationRef ? $value : null;
    }

    public function move(int $fromPublicationId, int $toPublicationId): void
    {
        if ($fromPublicationId === $toPublicationId) {
            return;
        }

        $ref = $this->find($fromPublicationId);
        if ($ref === null) {
            return;
        }

        $this->remember($toPublicationId, $ref);
        $this->forget($fromPublicationId);
    }

    public function forget(int $publicationId): void
    {
        $this->cache->delete(self::key($publicationId));
    }

    private static function key(int $publicationId): string
    {
        return self::KEY_PREFIX . $publicationId;
    }
}
