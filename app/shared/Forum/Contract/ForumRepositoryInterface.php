<?php

declare(strict_types=1);

namespace app\shared\Forum\Contract;

/**
 * Storage boundary for the forum parser.
 */
interface ForumRepositoryInterface
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
     * Upserts the topic and its author. Returns true when a new row was inserted.
     */
    public function save(\app\shared\Forum\Dto\TopicData $topic, string $now): bool;
}
