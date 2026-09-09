<?php

declare(strict_types=1);

namespace app\shared\Telegram\Contract;

use app\shared\Telegram\Dto\ChannelInfo;
use app\shared\Telegram\Dto\PostResult;

interface TelegramChannelClientInterface
{
    public function getChannelInfo(string $channelId): ChannelInfo;

    /**
     * @throws \app\shared\Telegram\Infrastructure\TelegramApiException
     */
    public function setChannelDescription(string $channelId, string $description): void;

    /**
     * @throws \app\shared\Telegram\Infrastructure\TelegramApiException
     */
    public function sendTextMessage(string $channelId, string $text): PostResult;

    /**
     * @throws \app\shared\Telegram\Infrastructure\TelegramApiException
     */
    public function sendPhotoMessage(string $channelId, string $photoPath, string $caption): PostResult;

    /**
     * @throws \app\shared\Telegram\Infrastructure\TelegramApiException
     */
    public function pinChannelMessage(string $channelId, int $messageId): void;
}
