<?php

declare(strict_types=1);

namespace app\shared\Publications\Service;

use app\shared\Publications\Contract\PublicationRepositoryInterface;
use app\shared\Publications\Dto\PublicationData;
use app\shared\Telegram\Infrastructure\TelegramApiException;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Use-cases for channel publications: saving form data as drafts or
 * scheduled posts, and sending due scheduled posts to Telegram.
 *
 * Form saves touch the database only; delivery to Telegram happens
 * exclusively in publishDue(), which is run by the periodic task.
 */
final class PublicationsService
{
    private const TEXT_MAX_LENGTH = 4096;

    public function __construct(
        private readonly PublicationRepositoryInterface $publications,
        private ?LoggerInterface $logger,
        private readonly object $channel,
    ) {
    }

    /**
     * @return PublicationData[] scheduled and published posts, newest first
     */
    public function posts(): array
    {
        return $this->publications->allPosts();
    }

    /**
     * @return PublicationData[] drafts, newest first
     */
    public function drafts(): array
    {
        return $this->publications->allDrafts();
    }

    /**
     * Saves the form data as a draft.
     *
     * @param string[] $imageUrls
     * @throws InvalidArgumentException when the text is empty
     */
    public function saveDraft(string $text, array $imageUrls): void
    {
        $this->assertTextValid($text);
        $this->publications->createDraft($text, $imageUrls, $this->now());
    }

    /**
     * Saves the form data as a scheduled (or immediately due) post.
     *
     * @param string[] $imageUrls
     * @throws InvalidArgumentException when the text is empty or the date is invalid
     */
    public function schedulePost(string $text, array $imageUrls, string $publishedAt): void
    {
        $this->assertTextValid($text);
        $normalized = $this->normalizeDate($publishedAt);
        $this->publications->createPost($text, $imageUrls, $normalized, $this->now());
    }

    /**
     * Sends all due posts (empty telegram_id, published_at <= now)
     * to the channel, from the smallest id to the biggest one.
     *
     * Each post is published independently: a failure is logged and
     * does not stop the remaining posts; failed posts keep an empty
     * telegram_id and are retried on the next run.
     *
     * @return array{processed: int, published: int, failed: int}
     */
    public function publishDue(): array
    {
        $stats = ['processed' => 0, 'published' => 0, 'failed' => 0];
        $now = $this->now();

        foreach ($this->publications->findDueForPublishing($now) as $post) {
            $stats['processed']++;

            try {
                $telegramId = $this->publishToTelegram($post);
                $this->publications->storeTelegramId($post->id, $telegramId, $this->now());
                $stats['published']++;
            } catch (TelegramApiException $e) {
                $stats['failed']++;
                $this->logger?->error(
                    'Publication {id} failed to reach Telegram: {error}',
                    ['id' => $post->id, 'error' => $e->getMessage()],
                );
            }
        }

        return $stats;
    }

    /**
     * Publishes a single post through the Telegram channel service.
     *
     * @throws RuntimeException when the bot token is not configured
     * @throws TelegramApiException on API failure
     */
    private function publishToTelegram(PublicationData $post): int
    {
        if (!method_exists($this->channel, 'publishText')) {
            throw new RuntimeException('Telegram-канал не сконфигурирован.');
        }

        return $this->channel->publishText($post->text);
    }

    /**
     * @throws InvalidArgumentException when the text is empty or longer than 4096 chars
     */
    private function assertTextValid(string $text): void
    {
        $length = mb_strlen($text);
        if ($length === 0 || $length > self::TEXT_MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Текст публикации должен быть от 1 до %d символов.', self::TEXT_MAX_LENGTH),
            );
        }
    }

    /**
     * @throws InvalidArgumentException when the date cannot be parsed
     */
    private function normalizeDate(string $publishedAt): string
    {
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd/m/Y h:i A',
            'd/m/Y h:i a',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $publishedAt, new DateTimeZone('UTC'));
            if ($date instanceof DateTimeImmutable) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        $parsed = strtotime($publishedAt);
        if ($parsed !== false) {
            return gmdate('Y-m-d H:i:s', $parsed);
        }

        throw new InvalidArgumentException(
            sprintf('Некорректная дата и время публикации: "%s".', $publishedAt),
        );
    }

    private function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }
}
