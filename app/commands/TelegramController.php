<?php

declare(strict_types=1);

namespace app\commands;

use app\shared\Telegram\Infrastructure\TelegramApiException;
use app\shared\Telegram\Service\ChannelService;
use InvalidArgumentException;
use yii\console\Controller;
use yii\console\ExitCode;
use Yii;

/**
 * TRVL channel management via Telegram Bot API.
 */
final class TelegramController extends Controller
{
    public function __construct($id, $module, private readonly ChannelService $channel, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    /**
     * Show current TRVL channel info (id, title, username, description).
     */
    public function actionIndex(): int
    {
        if (!$this->channel->isConfigured()) {
            $this->stderr("Telegram-бот не сконфигурирован: задайте TELEGRAM_BOT_TOKEN в .env\n");

            return ExitCode::CONFIG;
        }

        try {
            $info = $this->channel->channelInfo();
        } catch (TelegramApiException $e) {
            $this->stderr("Telegram API error [{$e->errorCode}]: {$e->getMessage()}\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Канал: @{$info->username} (#{$info->id})\n");
        $this->stdout("Название: {$info->title}\n");
        $this->stdout("Тип: {$info->type}\n");
        $this->stdout("Описание: {$info->description}\n");

        return ExitCode::OK;
    }

    /**
     * Set the TRVL channel description (slagon = description).
     *
     * @param string|null $description new description (0-255 chars); omit to clear
     */
    public function actionSetDescription(?string $description = null): int
    {
        try {
            $this->channel->updateDescription($description ?? '');
        } catch (InvalidArgumentException $e) {
            $this->stderr("Ошибка: {$e->getMessage()}\n");

            return ExitCode::DATAERR;
        } catch (TelegramApiException $e) {
            $this->stderr("Telegram API error [{$e->errorCode}]: {$e->getMessage()}\n");

            return ExitCode::UNSPECIFIED_ERROR;
        } catch (\RuntimeException $e) {
            $this->stderr("Ошибка: {$e->getMessage()}\n");

            return ExitCode::CONFIG;
        }

        $this->stdout("Описание канала обновлено.\n");

        return ExitCode::OK;
    }

    /**
     * Send a test text message to the channel.
     *
     * @param string $text message text
     * @param bool $pin whether to pin the sent message
     */
    public function actionTestPost(string $text, bool $pin = false): int
    {
        try {
            $messageId = $this->channel->publishText($text);
            if ($pin) {
                $this->channel->pinPost($messageId);
            }
        } catch (InvalidArgumentException $e) {
            $this->stderr("Ошибка: {$e->getMessage()}\n");

            return ExitCode::DATAERR;
        } catch (TelegramApiException $e) {
            $this->stderr("Telegram API error [{$e->errorCode}]: {$e->getMessage()}\n");

            return ExitCode::UNSPECIFIED_ERROR;
        } catch (\RuntimeException $e) {
            $this->stderr("Ошибка: {$e->getMessage()}\n");

            return ExitCode::CONFIG;
        }

        $this->stdout("Опубликовано, message_id={$messageId}\n");

        return ExitCode::OK;
    }
}
