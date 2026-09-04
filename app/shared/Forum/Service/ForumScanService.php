<?php

declare(strict_types=1);

namespace app\shared\Forum\Service;

use app\shared\Forum\Contract\ForumHttpClientInterface;
use app\shared\Forum\Contract\ForumRepositoryInterface;
use app\shared\Forum\Dto\TopicData;
use app\shared\Forum\Infrastructure\ForumLoginRequiredException;
use app\shared\Forum\Infrastructure\ForumPageNotFoundException;
use app\shared\Forum\Service\ForumHtmlParser;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\LoggerInterface;

/**
 * Iterates the configured topic id range, parses every page and upserts
 * the topic and its author. A failure of a single URL never stops the run.
 */
final class ForumScanService
{
    public function __construct(
        private readonly ForumRepositoryInterface $repository,
        private readonly ForumHttpClientInterface $httpClient,
        private readonly ForumHtmlParser $parser,
        private readonly LoggerInterface $logger,
        private readonly string $configCode = 'awd_forum_topics',
    ) {
    }

    /**
     * @param int|null $from explicit range start override
     * @param int|null $to explicit range end override
     * @param int|null $limit max number of topic ids to process in this run
     * @return array{processed: int, saved: int, updated: int, not_found: int, login_required: int, failed: int}|null null when another scan is already running
     */
    public function run(?int $from = null, ?int $to = null, ?int $limit = null): ?array
    {
        if (!$this->repository->acquireLock($this->configCode)) {
            $this->logger->warning('Forum scan is already running, launch skipped.', ['code' => $this->configCode]);
            return null;
        }

        try {
            return $this->doRun($from, $to, $limit);
        } finally {
            $this->repository->releaseLock($this->configCode);
        }
    }

    /**
     * @return array{processed: int, saved: int, updated: int, not_found: int, login_required: int, failed: int}
     */
    private function doRun(?int $from, ?int $to, ?int $limit): array
    {
        $config = $this->repository->activeConfig($this->configCode);
        if ($config === null) {
            $this->logger->warning('Parser config is missing or disabled.', ['code' => $this->configCode]);
            return ['processed' => 0, 'saved' => 0, 'updated' => 0, 'not_found' => 0, 'login_required' => 0, 'failed' => 0];
        }

        $start = max((int)$config['t_from'], $from ?? (int)$config['t_from']);
        $end = min((int)$config['t_to'], $to ?? (int)$config['t_to']);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $stats = ['processed' => 0, 'saved' => 0, 'updated' => 0, 'not_found' => 0, 'login_required' => 0, 'failed' => 0];

        for ($id = $start; $id <= $end && ($limit === null || $stats['processed'] < $limit); $id++) {
            $stats['processed']++;
            $url = rtrim((string)$config['base_url'], '=') . '=' . $id;
            try {
                $topic = $this->parser->parse($id, $url, $this->httpClient->get($url), $now);
                $isNew = $this->repository->save($topic, $now->format('Y-m-d H:i:s'));
                $isNew ? $stats['saved']++ : $stats['updated']++;
            } catch (ForumPageNotFoundException $e) {
                $stats['not_found']++;
                $this->logger->info($e->getMessage(), ['topic_id' => $id]);
            } catch (ForumLoginRequiredException $e) {
                $stats['login_required']++;
                $this->logger->info($e->getMessage(), ['topic_id' => $id]);
                $isNew = $this->repository->save(TopicData::loginRequired($id, $url), $now->format('Y-m-d H:i:s'));
                $isNew ? $stats['saved']++ : $stats['updated']++;
            } catch (\Throwable $e) {
                $stats['failed']++;
                $this->logger->warning('Forum topic skipped.', ['topic_id' => $id, 'error' => $e->getMessage()]);
            }
        }

        $this->repository->markRun($this->configCode, $now->format('Y-m-d H:i:s'));
        $this->logger->info('Forum scan finished.', array_merge(['code' => $this->configCode], $stats));
        return $stats;
    }
}
