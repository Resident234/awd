<?php

declare(strict_types=1);

namespace app\shared\Settings\Contract;

/**
 * Storage of the publication settings: one row per tunable, addressed by
 * its code. The whole table is read at once, because every page of the
 * portal needs most of the values, and it is written at once, because the
 * settings form saves all of them together.
 */
interface PublicationSettingsRepositoryInterface
{
    /**
     * Every stored value as code => value.
     *
     * @return array<string, string>
     */
    public function all(): array;

    /**
     * Stores the values, one row per code, in a single transaction: a
     * failed save leaves the whole table as it was.
     *
     * @param array<string, string> $values
     */
    public function saveMany(array $values, string $now): void;
}
