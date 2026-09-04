<?php

declare(strict_types=1);

namespace app\shared\Forum\Contract;

interface ForumHttpClientInterface
{
    public function get(string $url): string;
}
