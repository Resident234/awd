<?php

declare(strict_types=1);

namespace app\shared\Forum\Infrastructure;

use RuntimeException;

/**
 * Raised when the topic belongs to a forum section available
 * to authorized users only.
 */
final class ForumLoginRequiredException extends RuntimeException
{
}
