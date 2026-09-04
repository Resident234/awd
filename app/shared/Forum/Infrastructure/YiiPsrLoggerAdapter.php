<?php

declare(strict_types=1);

namespace app\shared\Forum\Infrastructure;

use Psr\Log\AbstractLogger;
use yii\log\Logger as YiiLogger;

/**
 * PSR-3 adapter over the Yii logger, so Shared services stay
 * framework-agnostic at the contract level.
 */
final class YiiPsrLoggerAdapter extends AbstractLogger
{
    public function __construct(private readonly YiiLogger $logger)
    {
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $levelMap = [
            'emergency' => YiiLogger::LEVEL_ERROR,
            'alert' => YiiLogger::LEVEL_ERROR,
            'critical' => YiiLogger::LEVEL_ERROR,
            'error' => YiiLogger::LEVEL_ERROR,
            'warning' => YiiLogger::LEVEL_WARNING,
            'notice' => YiiLogger::LEVEL_WARNING,
            'info' => YiiLogger::LEVEL_INFO,
            'debug' => YiiLogger::LEVEL_TRACE,
        ];
        $contextMessage = $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->logger->log(
            (string)$message . $contextMessage,
            $levelMap[$level] ?? YiiLogger::LEVEL_INFO,
            'forum-parser'
        );
    }
}
