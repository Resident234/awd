<?php

declare(strict_types=1);

namespace app\shared\Forum\Dto;

/**
 * A forum member parsed either from a post profile block or from the
 * memberlist profile pages. id = the u parameter of the profile link.
 */
final readonly class MemberData
{
    public function __construct(
        public int $id,
        public string $profileUrl,
        public string $name,
        public ?string $avatarUrl,
        public ?string $rankName,
        public ?int $messagesCount,
        public ?string $registeredOn,
        public ?string $city,
        public ?int $thanksGivenCount,
        public ?int $thanksReceivedCount,
        public ?int $age,
        public ?int $countriesCount,
        public ?int $reportsCount,
        public ?string $gender,
        public array $rawData,
        public bool $profileLoginRequired = false,
        public ?string $lastVisitAt = null,
        public ?int $photosCount = null,
    ) {
    }

    /**
     * Minimal stub for a profile page hidden behind forum authorization:
     * only the id and the profile url are known.
     */
    public static function profileLoginRequired(int $id, string $profileUrl): self
    {
        return new self(
            $id,
            $profileUrl,
            '',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            [],
            true,
        );
    }
}
