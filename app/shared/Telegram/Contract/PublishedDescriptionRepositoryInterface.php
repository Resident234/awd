<?php

declare(strict_types=1);

namespace app\shared\Telegram\Contract;

use app\shared\Telegram\Dto\PublishedDescriptionData;

/**
 * Storage boundary for the published channel description archive.
 */
interface PublishedDescriptionRepositoryInterface
{
    /**
     * @return PublishedDescriptionData[] ordered by published_from DESC
     */
    public function all(): array;

    /**
     * Closes the currently active row (sets published_to = $now) and
     * inserts a new one in a single transaction. When no active row
     * exists only the new row is inserted.
     */
    public function archiveAndStart(string $description, string $now): void;
}
