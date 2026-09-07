<?php

declare(strict_types=1);

namespace app\shared\Forum\Service;

use app\shared\Forum\Contract\ForumHttpClientInterface;
use app\shared\Forum\Contract\ForumRepositoryInterface;
use app\shared\Forum\Infrastructure\ForumLoginRequiredException;
use app\shared\Forum\Infrastructure\ForumPageNotFoundException;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\LoggerInterface;

/**
 * Iterates topics stored in the topic table, walks every page of each
 * topic through the phpBB pagination and upserts all posts and their
 * authors. A failure of a single topic or page never stops the run.
 */
final class ForumPostScanService
{
    private const MAX_PAGES_PER_TOPIC = 10000;

    public function __construct(
        private readonly ForumRepositoryInterface $repository,
        private readonly ForumHttpClientInterface $httpClient,
        private readonly ForumPostPageParser $parser,
        private readonly LoggerInterface $logger,
        private readonly string $configCode = 'awd_forum_posts',
    ) {
    }

    /**
     * @param int|null $from explicit topic id range start override
     * @param int|null $to explicit topic id range end override
     * @param int|null $limit max number of topics to process in this run
     * @param int|null $pageLimit max number of pages to fetch per topic in this run
     * @return array{processed: int, pages: int, posts_saved: int, posts_updated: int, topics_failed: int, topics_not_found: int, topics_login_required: int, topics_skipped_no_posts: int}|null null when another scan is already running
     */
    public function run(?int $from = null, ?int $to = null, ?int $limit = null, ?int $pageLimit = null): ?array
    {
        if (!$this->repository->acquireLock($this->configCode)) {
            $this->logger->warning('Forum post scan is already running, launch skipped.', ['code' => $this->configCode]);
            return null;
        }

        try {
            return $this->doRun($from, $to, $limit, $pageLimit);
        } finally {
            $this->repository->releaseLock($this->configCode);
        }
    }

    /**
     * @return array{processed: int, pages: int, posts_saved: int, posts_updated: int, topics_failed: int, topics_not_found: int, topics_login_required: int, topics_skipped_no_posts: int}
     */
    private function doRun(?int $from, ?int $to, ?int $limit, ?int $pageLimit): array
    {
        $stats = $this->emptyStats();
        $config = $this->repository->activeConfig($this->configCode);
        if ($config === null) {
            $this->logger->warning('Parser config is missing or disabled.', ['code' => $this->configCode]);
            return $stats;
        }

        $start = max((int)$config['t_from'], $from ?? (int)$config['t_from']);
        $end = min((int)$config['t_to'], $to ?? (int)$config['t_to']);
        $topicIds = $this->repository->existingTopicIds($start, $end);
        $baseUrl = rtrim((string)$config['base_url'], '=');

        foreach ($topicIds as $topicId) {
            if ($limit !== null && $stats['processed'] >= $limit) {
                break;
            }
            $stats['processed']++;
            $topicStats = $this->scanTopic((int)$topicId, $baseUrl . '=' . $topicId, $pageLimit);
            foreach ($topicStats as $key => $value) {
                $stats[$key] += $value;
            }
        }

        $endNow = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->repository->markRun($this->configCode, $endNow->format('Y-m-d H:i:s'));
        $this->logger->info('Forum post scan finished.', array_merge(['code' => $this->configCode], $stats));
        return $stats;
    }

    /**
     * @return array{processed: int, pages: int, posts_saved: int, posts_updated: int, topics_failed: int, topics_not_found: int, topics_login_required: int, topics_skipped_no_posts: int}
     */
    private function scanTopic(int $topicId, string $topicUrl, ?int $pageLimit): array
    {
        $stats = $this->emptyStats();
        $pageUrl = $topicUrl;
        $pages = 0;
        // phpBB repeats the topic opening post at the top of every page
        // with a position-based message number; posts already saved from
        // earlier pages of the same topic must not be overwritten.
        $seenPostIds = [];

        try {
            while ($pageUrl !== null) {
                if ($pages >= self::MAX_PAGES_PER_TOPIC || ($pageLimit !== null && $pages >= $pageLimit)) {
                    break;
                }
                // Fresh timestamp per page: updated_at shows the actual refresh moment.
                $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                $html = $this->httpClient->get($pageUrl);
                $posts = $this->parser->parse($topicId, $pageUrl, $html, $now);
                if ($posts === []) {
                    if (str_contains($html, 'вы должны быть авторизованы')) {
                        throw new ForumLoginRequiredException('Topic is available to authorized users only.');
                    }
                    if ($pages === 0) {
                        $stats['topics_skipped_no_posts']++;
                        $this->logger->info('Topic page contains no posts.', ['topic_id' => $topicId, 'url' => $pageUrl]);
                        return $stats;
                    }
                    break;
                }
                $freshPosts = [];
                foreach ($posts as $post) {
                    if (isset($seenPostIds[$post->id])) {
                        continue;
                    }
                    $seenPostIds[$post->id] = true;
                    $freshPosts[] = $post;
                }
                if ($freshPosts !== []) {
                    $pages++;
                    $stats['pages']++;
                    $nowSql = $now->format('Y-m-d H:i:s');
                    $saved = $this->repository->savePosts($freshPosts, $nowSql);
                    $stats['posts_saved'] += $saved;
                    $stats['posts_updated'] += count($freshPosts) - $saved;
                }
                $pageUrl = $this->parser->parseNextPageUrl($pageUrl, $html);
            }
        } catch (ForumPageNotFoundException $e) {
            $stats['topics_not_found']++;
            $this->logger->info($e->getMessage(), ['topic_id' => $topicId]);
        } catch (ForumLoginRequiredException $e) {
            $stats['topics_login_required']++;
            $this->logger->info($e->getMessage(), ['topic_id' => $topicId]);
        } catch (\Throwable $e) {
            $stats['topics_failed']++;
            $this->logger->warning('Forum topic posts scan failed.', ['topic_id' => $topicId, 'error' => $e->getMessage()]);
        }
        return $stats;
    }

    /**
     * @return array{processed: int, pages: int, posts_saved: int, posts_updated: int, topics_failed: int, topics_not_found: int, topics_login_required: int, topics_skipped_no_posts: int}
     */
    private function emptyStats(): array
    {
        return [
            'processed' => 0,
            'pages' => 0,
            'posts_saved' => 0,
            'posts_updated' => 0,
            'topics_failed' => 0,
            'topics_not_found' => 0,
            'topics_login_required' => 0,
            'topics_skipped_no_posts' => 0,
        ];
    }
}
