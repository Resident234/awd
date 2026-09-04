<?php

declare(strict_types=1);

namespace app\shared\Forum\Infrastructure;

use RuntimeException;

/**
 * Raised when the forum answers that the requested topic does not exist.
 */
final class ForumPageNotFoundException extends RuntimeException
{
}
