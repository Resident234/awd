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
                $sentAt = $this->now();
                $this->publications->storeTelegramId($post->id, $telegramId, $sentAt, $sentAt);
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
     * Saves the form data of a post or draft opened for editing.
     *
     * Six cross-table scenarios depending on the source record and the
     * pressed button:
     *
     *  - scheduled post + "Опубликовать": update the posts row in place;
     *  - scheduled post + "Сохранить": move to drafts;
     *  - published post + "Опубликовать": move to publications_edited;
     *  - published post + "Сохранить": move to drafts;
     *  - draft + "Опубликовать": move to posts;
     *  - draft + "Сохранить": update the drafts row in place.
     *
     * created_at is always preserved; updated_at is set to the current
     * time on every move or update.
     *
     * @param string[] $imageUrls
     * @throws InvalidArgumentException when the text is empty, the date
     * is invalid or the source record does not exist
     */
    public function saveFromForm(string $text, array $imageUrls, string $publishedAt, string $source, ?int $sourceId, string $action): void
    {
        $this->assertTextValid($text);
        $now = $this->now();

        if ($source === 'draft') {
            $this->assertDraftExists($sourceId);
            if ($action === 'draft') {
                // 6) draft + "Сохранить": update in place.
                $this->publications->updateDraft($sourceId, $text, $imageUrls, $now);

                return;
            }

            // 5) draft + "Опубликовать": move to posts.
            $draft = $this->publications->deleteDraft($sourceId);
            $this->publications->insertPostWithHistory(
                new PublicationData(
                    $draft->id,
                    $text,
                    $imageUrls,
                    null,
                    $this->normalizeDate($publishedAt),
                    $draft->createdAt,
                    $draft->updatedAt,
                ),
                $now,
            );

            return;
        }

        $this->assertPostExists($sourceId);
        $post = $this->publications->findPost($sourceId);
        $isPublished = $post !== null && $post->telegramId !== null;

        if ($isPublished) {
            if ($action === 'publish') {
                // 3) published post + "Опубликовать": archive to publications_edited.
                $this->publications->archiveEdited($post, $now);
                $this->publications->deletePost($sourceId);

                return;
            }

            // 4) published post + "Сохранить": move to drafts.
            $this->publications->deletePost($sourceId);
            $this->publications->insertDraftWithHistory($post, $now);

            return;
        }

        if ($action === 'publish') {
            // 1) scheduled post + "Опубликовать": update in place.
            $this->publications->updatePost($sourceId, $text, $imageUrls, $this->normalizeDate($publishedAt), $now);

            return;
        }

        // 2) scheduled post + "Сохранить": move to drafts.
        $this->publications->deletePost($sourceId);
        $this->publications->insertDraftWithHistory(
            new PublicationData(
                $post->id,
                $text,
                $imageUrls,
                null,
                null,
                $post->createdAt,
                $post->updatedAt,
            ),
            $now,
        );
    }

    /**
     * @throws InvalidArgumentException when the record does not exist
     */
    private function assertPostExists(?int $id): void
    {
        if ($id === null || $this->publications->findPost($id) === null) {
            throw new InvalidArgumentException(
                $id === null ? 'Не указана редактируемая публикация.' : "Публикация #{$id} не найдена.",
            );
        }
    }

    /**
     * @throws InvalidArgumentException when the record does not exist
     */
    private function assertDraftExists(?int $id): void
    {
        if ($id === null || $this->publications->findDraft($id) === null) {
            throw new InvalidArgumentException(
                $id === null ? 'Не указан редактируемый черновик.' : "Черновик #{$id} не найден.",
            );
        }
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
