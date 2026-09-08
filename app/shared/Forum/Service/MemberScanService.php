<?php

declare(strict_types=1);

namespace app\shared\Forum\Service;

use app\shared\Forum\Contract\ForumHttpClientInterface;
use app\shared\Forum\Contract\ForumRepositoryInterface;
use app\shared\Forum\Dto\MemberData;
use app\shared\Forum\Infrastructure\ForumLoginRequiredException;
use app\shared\Forum\Infrastructure\ForumPageNotFoundException;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\LoggerInterface;

/**
 * Iterates the configured member id range (u parameter), fetches the
 * profile main page and the statistics tab (page=7) of every member and
 * upserts the merged profile. A failure of a single member never stops
 * the run.
 */
final class MemberScanService
{
    public function __construct(
        private readonly ForumRepositoryInterface $repository,
        private readonly ForumHttpClientInterface $httpClient,
        private readonly MemberProfilePageParser $parser,
        private readonly LoggerInterface $logger,
        private readonly string $configCode = 'awd_forum_members',
    ) {
    }

    /**
     * @param int|null $from explicit range start override
     * @param int|null $to explicit range end override
     * @param int|null $limit max number of member ids to process in this run
     * @return array{processed: int, saved: int, updated: int, not_found: int, login_required: int, failed: int}|null null when another scan is already running
     */
    public function run(?int $from = null, ?int $to = null, ?int $limit = null): ?array
    {
        if (!$this->repository->acquireLock($this->configCode)) {
            $this->logger->warning('Member scan is already running, launch skipped.', ['code' => $this->configCode]);
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
        $stats = ['processed' => 0, 'saved' => 0, 'updated' => 0, 'not_found' => 0, 'login_required' => 0, 'failed' => 0];

        for ($id = $start; $id <= $end && ($limit === null || $stats['processed'] < $limit); $id++) {
            $stats['processed']++;
            $url = rtrim((string)$config['base_url'], '=') . '=' . $id;
            try {
                // A fresh timestamp per member: updated_at must show when
                // the row was actually refreshed, not when the run started.
                $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                $member = $this->parser->parseMain($id, $url, $this->httpClient->get($url));
                $statsUrl = $url . '&page=7';
                $statistics = $this->parser->parseStatistics($statsUrl, $this->httpClient->get($statsUrl), $now);

                $member = new MemberData(
                    $member->id,
                    $member->profileUrl,
                    $member->name,
                    $member->avatarUrl,
                    $member->rankName,
                    $statistics['messages_count'],
                    $this->toDate($statistics['registered_at']),
                    $member->city,
                    null,
                    null,
                    $member->age,
                    null,
                    null,
                    $member->gender,
                    array_merge($member->rawData, [
                        'Зарегистрирован' => $statistics['registered_at'],
                        'Последнее посещение' => $statistics['last_visit_at'],
                        'Фотографий' => (string)($statistics['photos_count'] ?? ''),
                    ]),
                    false,
                    $statistics['last_visit_at'],
                    $statistics['photos_count'],
                );
                $isNew = $this->repository->saveMemberProfile($member, $now->format('Y-m-d H:i:s'));
                $isNew ? $stats['saved']++ : $stats['updated']++;
            } catch (ForumPageNotFoundException $e) {
                $stats['not_found']++;
                $this->logger->info($e->getMessage(), ['member_id' => $id]);
            } catch (ForumLoginRequiredException $e) {
                $stats['login_required']++;
                $this->logger->info($e->getMessage(), ['member_id' => $id]);
                $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                $isNew = $this->repository->saveMemberProfile(
                    MemberData::profileLoginRequired($id, $url),
                    $now->format('Y-m-d H:i:s')
                );
                $isNew ? $stats['saved']++ : $stats['updated']++;
            } catch (\Throwable $e) {
                $stats['failed']++;
                $this->logger->warning('Member skipped.', ['member_id' => $id, 'error' => $e->getMessage()]);
            }
        }

        $endNow = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->repository->markRun($this->configCode, $endNow->format('Y-m-d H:i:s'));
        $this->logger->info('Member scan finished.', array_merge(['code' => $this->configCode], $stats));
        return $stats;
    }

    /**
     * "2011-12-24 22:42:00" (site timezone) -> "2011-12-24" for registered_on.
     */
    private function toDate(?string $dateTime): ?string
    {
        if ($dateTime === null) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTime);
        return $date === false ? null : $date->format('Y-m-d');
    }
}
