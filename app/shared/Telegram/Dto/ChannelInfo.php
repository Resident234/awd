<?php

declare(strict_types=1);

namespace app\shared\Telegram\Dto;

final class ChannelInfo
{
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly ?string $title,
        public readonly ?string $username,
        public readonly ?string $description,
    ) {
    }
}
