<?php

declare(strict_types=1);

namespace app\shared\Telegram\Service;

use app\shared\Telegram\Contract\PublishedDescriptionRepositoryInterface;
use app\shared\Telegram\Contract\TelegramChannelClientInterface;
use app\shared\Telegram\Dto\ChannelInfo;
use app\shared\Telegram\Dto\PublishedDescriptionData;
use app\shared\Telegram\Infrastructure\TelegramApiException;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;

/**
 * Use-cases for the TRVL channel: description management and posting.
 *
 * Every method that talks to the Telegram API can additionally throw
 * RuntimeException when the bot token is not configured.
 */
final class ChannelService
{
    private const DESCRIPTION_MAX_LENGTH = 255;
    private const TEXT_MAX_LENGTH = 4096;
    private const CAPTION_MAX_LENGTH = 1024;

    public function __construct(
        private readonly ?TelegramChannelClientInterface $client,
        private readonly string $channelId,
        private readonly PublishedDescriptionRepositoryInterface $publishedDescriptions,
    ) {
    }

    /**
     * Whether the Telegram bot is configured (token provided).
     */
    public function isConfigured(): bool
    {
        return $this->client !== null;
    }

    /**
     * @throws TelegramApiException on API failure
     * @throws RuntimeException when the bot token is not configured
     */
    public function channelInfo(): ChannelInfo
    {
        return $this->client()->getChannelInfo($this->channelId);
    }

    /**
     * @throws TelegramApiException on API failure
     * @throws RuntimeException when the bot token is not configured
     * @throws InvalidArgumentException when the description exceeds 255 chars
     */
    public function updateDescription(string $description): void
    {
        $length = mb_strlen($description);
        if ($length > self::DESCRIPTION_MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Описание канала не может быть длиннее %d символов (сейчас %d).', self::DESCRIPTION_MAX_LENGTH, $length),
            );
        }

        $this->client()->setChannelDescription($this->channelId, $description);

        $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $this->publishedDescriptions->archiveAndStart($description, $now);
    }

    /**
     * @return PublishedDescriptionData[] published descriptions, newest first
     */
    public function publishedDescriptions(): array
    {
        return $this->publishedDescriptions->all();
    }

    /**
     * @throws TelegramApiException on API failure
     * @throws RuntimeException when the bot token is not configured
     * @throws InvalidArgumentException when the text is empty or longer than 4096 chars
     */
    public function publishText(string $text): int
    {
        if (mb_strlen($text) === 0 || mb_strlen($text) > self::TEXT_MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Текст поста должен быть от 1 до %d символов.', self::TEXT_MAX_LENGTH),
            );
        }

        return $this->client()->sendTextMessage($this->channelId, $text)->messageId;
    }

    /**
     * @throws TelegramApiException on API failure
     * @throws InvalidArgumentException when the photo file is missing or the caption exceeds 1024 chars
     */
    public function publishPhoto(string $photoPath, string $caption): int
    {
        if (!is_file($photoPath)) {
            throw new InvalidArgumentException(sprintf('Файл изображения не найден: %s', $photoPath));
        }

        if (mb_strlen($caption) > self::CAPTION_MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Подпись к изображению не может быть длиннее %d символов.', self::CAPTION_MAX_LENGTH),
            );
        }

        return $this->client()->sendPhotoMessage($this->channelId, $photoPath, $caption)->messageId;
    }

    /**
     * @throws TelegramApiException on API failure
     */
    public function pinPost(int $messageId): void
    {
        $this->client()->pinChannelMessage($this->channelId, $messageId);
    }

    /**
     * @throws TelegramApiException on API failure
     * @throws RuntimeException when the bot token is not configured
     */
    public function deletePost(int $messageId): void
    {
        $this->client()->deleteChannelMessage($this->channelId, $messageId);
    }

    /**
     * @throws RuntimeException when the bot token is not configured
     */
    private function client(): TelegramChannelClientInterface
    {
        if ($this->client === null) {
            throw new RuntimeException(
                'Telegram-бот не сконфигурирован: задайте TELEGRAM_BOT_TOKEN в .env и перезапустите стек.',
            );
        }

        return $this->client;
    }
}
