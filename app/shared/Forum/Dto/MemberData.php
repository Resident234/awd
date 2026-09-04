<?php

declare(strict_types=1);

namespace app\shared\Forum\Dto;

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
    ) {
    }
}
