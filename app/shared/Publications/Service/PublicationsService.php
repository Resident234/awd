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
     * @return PublicationData[] scheduled and published posts,
     * sorted by published_at descending
     */
    public function posts(): array
    {
        return $this->publications->allPosts();
    }

    /**
     * @return PublicationData[] drafts, sorted by updated_at descending
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
     * Closes all pending soft-deleted records: publications_deleted
     * rows with an empty deleted_at are deleted from Telegram by
     * their telegram_id, from the smallest id to the biggest one, and
     * then stamped with the actual removal time. Records without a
     * telegram_id (drafts, scheduled posts) never had a message in
     * the channel and are stamped right away.
     *
     * Each record is processed independently: a failure is logged and
     * does not stop the remaining records; failed records keep an
     * empty deleted_at and are retried on the next run.
     *
     * @return array{processed: int, deleted: int, failed: int}
     */
    public function deleteDue(): array
    {
        $stats = ['processed' => 0, 'deleted' => 0, 'failed' => 0];

        foreach ($this->publications->findPendingChannelDeletion() as $record) {
            $stats['processed']++;

            if ($record->telegramId === null) {
                $this->publications->storeDeletedAt($record->id, $this->now());
                $stats['deleted']++;

                continue;
            }

            try {
                $this->deleteFromTelegram($record);
                $this->publications->storeDeletedAt($record->id, $this->now());
                $stats['deleted']++;
            } catch (TelegramApiException | RuntimeException $e) {
                $stats['failed']++;
                $this->logger?->error(
                    'Deleted publication {id} failed to leave Telegram: {error}',
                    ['id' => $record->id, 'error' => $e->getMessage()],
                );
            }
        }

        return $stats;
    }

    /**
     * Updates all pending archived edits in the channel:
     * publications_edited rows with an empty edited_at get their
     * message content replaced with the record's text field by the
     * telegram_id, from the smallest id to the biggest one, and then
     * stamped with the actual update time.
     *
     * Each record is processed independently: a failure is logged and
     * does not stop the remaining records; failed records keep an
     * empty edited_at and are retried on the next run.
     *
     * @return array{processed: int, edited: int, failed: int}
     */
    public function editDue(): array
    {
        $stats = ['processed' => 0, 'edited' => 0, 'failed' => 0];

        foreach ($this->publications->findPendingChannelEdits() as $record) {
            $stats['processed']++;

            try {
                $this->editInTelegram($record);
                $this->publications->storeEditedAt($record->id, $this->now());
                $stats['edited']++;
            } catch (TelegramApiException | RuntimeException | InvalidArgumentException $e) {
                $stats['failed']++;
                $this->logger?->error(
                    'Edited publication {id} failed to reach Telegram: {error}',
                    ['id' => $record->id, 'error' => $e->getMessage()],
                );
            }
        }

        return $stats;
    }

    /**
     * Soft-deletes a post by the "Удалить" button: the record moves
     * to publications_deleted. created_at and published_at keep
     * their values, updated_at is set to the current time, deleted_at
     * stays empty — the periodic deleteDue() task removes the message
     * from the channel (when it has one) and stamps deleted_at.
     *
     * @throws InvalidArgumentException when the post does not exist
     */
    public function deletePost(int $id): void
    {
        $post = $this->publications->deletePost($id);
        $this->publications->insertDeletedWithHistory($post, $this->now());
    }

    /**
     * Soft-deletes a draft by the "Удалить" button: the record moves
     * to publications_deleted. created_at keeps its value (the draft
     * has no published_at, it stays NULL), updated_at is set to the
     * current time, deleted_at stays empty.
     *
     * @throws InvalidArgumentException when the draft does not exist
     */
    public function deleteDraft(int $id): void
    {
        $draft = $this->publications->deleteDraft($id);
        $this->publications->insertDeletedWithHistory($draft, $this->now());
    }

    /**
     * Publishes a draft directly by the "Опубликовать" button in the
     * drafts list, without opening the editing form.
     *
     * The draft row is deleted and inserted into the posts table:
     * published_at and updated_at are set to the current time,
     * created_at is preserved. The post is due immediately, so the
     * periodic publishDue() task sends it to Telegram.
     *
     * @throws InvalidArgumentException when the draft does not exist
     */
    public function publishDraft(int $id): void
    {
        $draft = $this->publications->deleteDraft($id);
        $now = $this->now();
        $this->publications->insertPostWithHistory(
            new PublicationData(
                $draft->id,
                $draft->text,
                $draft->imageUrls,
                null,
                $now,
                $draft->createdAt,
                $draft->updatedAt,
            ),
            $now,
        );
    }

    /**
     * Schedules a draft by the "Запланировать публикацию" modal:
     * the draft moves to the posts table with the publication time
     * from the modal's date-time field. created_at is preserved,
     * updated_at is set to the current time; the periodic
     * publishDue() task sends the post when the time comes.
     *
     * @throws InvalidArgumentException when the draft does not exist or the date is invalid
     */
    public function scheduleDraft(int $id, string $publishedAt): void
    {
        $this->assertDraftExists($id);
        $draft = $this->publications->deleteDraft($id);
        $now = $this->now();
        $this->publications->insertPostWithHistory(
            new PublicationData(
                $draft->id,
                $draft->text,
                $draft->imageUrls,
                null,
                $this->normalizeDate($publishedAt),
                $draft->createdAt,
                $draft->updatedAt,
            ),
            $now,
        );
    }

    /**
     * Makes a scheduled post due immediately by the "Опубликовать"
     * button in the posts list, without opening the editing form.
     *
     * published_at and updated_at are set to the current time, so the
     * periodic publishDue() task sends it to Telegram on its next run.
     *
     * @throws InvalidArgumentException when the post does not exist
     */
    public function publishPostNow(int $id): void
    {
        $post = $this->publications->findPost($id);
        if ($post === null) {
            throw new InvalidArgumentException("Публикация #{$id} не найдена.");
        }

        $now = $this->now();
        $this->publications->updatePost($id, $post->text, $post->imageUrls, $now, $now);
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
     * Removes a soft-deleted record's message from the channel.
     *
     * @throws RuntimeException when the bot token is not configured
     * @throws TelegramApiException on API failure
     */
    private function deleteFromTelegram(PublicationData $record): void
    {
        if (!method_exists($this->channel, 'deletePost')) {
            throw new RuntimeException('Telegram-канал не сконфигурирован.');
        }

        if ($record->telegramId === null) {
            throw new RuntimeException(
                "Публикация #{$record->id} не имеет telegram_id — нечего удалять из канала.",
            );
        }

        $this->channel->deletePost($record->telegramId);
    }

    /**
     * Replaces an archived edit's message content in the channel
     * with the record's text field.
     *
     * @throws RuntimeException when the bot token is not configured
     * @throws TelegramApiException on API failure
     */
    private function editInTelegram(PublicationData $record): void
    {
        if (!method_exists($this->channel, 'editPostText')) {
            throw new RuntimeException('Telegram-канал не сконфигурирован.');
        }

        if ($record->telegramId === null) {
            throw new RuntimeException(
                "Публикация #{$record->id} не имеет telegram_id — нечего редактировать в канале.",
            );
        }

        $this->channel->editPostText($record->telegramId, $record->text);
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
     * Moves a post (scheduled or published) to drafts directly,
     * without opening the editing form.
     *
     * The post row is deleted from the posts table and inserted into
     * the drafts table: created_at is preserved, updated_at is set
     * to the current time (the moment of the move).
     *
     * @throws InvalidArgumentException when the post does not exist
     */
    public function movePostToDraft(int $id): void
    {
        $post = $this->publications->deletePost($id);
        $this->publications->insertDraftWithHistory($post, $this->now());
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
