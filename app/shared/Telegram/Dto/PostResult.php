<?php

declare(strict_types=1);

namespace app\shared\Telegram\Dto;

final class PostResult
{
    public function __construct(
        public readonly int $messageId,
        public readonly int $date,
    ) {
    }
}
