<?php

declare(strict_types=1);

namespace app\shared\Telegram\Infrastructure;

use app\shared\Telegram\Contract\TelegramChannelClientInterface;
use app\shared\Telegram\Dto\ChannelInfo;
use app\shared\Telegram\Dto\PostResult;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Exceptions\TelegramException;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\Message\Message;
use Throwable;

/**
 * Nutgram-based adapter. All SDK types stay inside: upper layers
 * receive and return DTOs only.
 */
final class NutgramChannelClient implements TelegramChannelClientInterface
{
    private Nutgram $_bot;

    public function __construct(string $token)
    {
        $this->_bot = new Nutgram($token);
    }

    public function getChannelInfo(string $channelId): ChannelInfo
    {
        $chat = $this->call(static fn (Nutgram $bot): ?Chat => $bot->getChat($channelId));

        return new ChannelInfo(
            $chat->id,
            is_string($chat->type) ? $chat->type : $chat->type->value,
            $chat->title,
            $chat->username,
            $chat->description,
        );
    }

    public function setChannelDescription(string $channelId, string $description): void
    {
        $this->call(static fn (Nutgram $bot): ?bool => $bot->setChatDescription($channelId, $description));
    }

    public function sendTextMessage(string $channelId, string $text): PostResult
    {
        $message = $this->call(
            static fn (Nutgram $bot): ?Message => $bot->sendMessage(
                chat_id: $channelId,
                text: $text,
            ),
        );

        return new PostResult($message->message_id, $message->date);
    }

    public function sendPhotoMessage(string $channelId, string $photoPath, string $caption): PostResult
    {
        $message = $this->call(
            static fn (Nutgram $bot): ?Message => $bot->sendPhoto(
                chat_id: $channelId,
                photo: $photoPath,
                caption: $caption,
            ),
        );

        return new PostResult($message->message_id, $message->date);
    }

    public function pinChannelMessage(string $channelId, int $messageId): void
    {
        $this->call(
            static fn (Nutgram $bot): ?bool => $bot->pinChatMessage(
                chat_id: $channelId,
                message_id: $messageId,
            ),
        );
    }

    public function deleteChannelMessage(string $channelId, int $messageId): void
    {
        $this->call(
            static fn (Nutgram $bot): ?bool => $bot->deleteMessage(
                chat_id: $channelId,
                message_id: $messageId,
            ),
        );
    }

    public function editChannelMessageText(string $channelId, int $messageId, string $text): void
    {
        $this->call(
            static fn (Nutgram $bot): bool => $bot->editMessageText(
                text: $text,
                chat_id: $channelId,
                message_id: $messageId,
            ) !== null,
        );
    }

    /**
     * @template T
     * @param callable(Nutgram): T $operation
     * @return T
     * @throws TelegramApiException
     */
    private function call(callable $operation): mixed
    {
        try {
            return $operation($this->_bot);
        } catch (TelegramException $e) {
            throw TelegramApiException::fromTelegramException($e);
        } catch (Throwable $e) {
            throw new TelegramApiException('Telegram API transport failure: ' . $e->getMessage(), 0, null, $e);
        }
    }
}
