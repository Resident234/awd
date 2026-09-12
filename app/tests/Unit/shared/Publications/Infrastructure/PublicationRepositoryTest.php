<?php

declare(strict_types=1);

namespace app\tests\Unit\shared\Publications\Infrastructure;

use app\shared\Publications\Dto\PublicationData;
use app\shared\Publications\Infrastructure\PublicationRepository;
use Codeception\Test\Unit;
use InvalidArgumentException;
use Yii;

final class PublicationRepositoryTest extends Unit
{
    private PublicationRepository $_repository;

    protected function _before(): void
    {
        parent::_before();

        Yii::$app->getDb()
            ->createCommand('TRUNCATE TABLE {{%publications_draft}} RESTART IDENTITY')
            ->execute();
        Yii::$app->getDb()
            ->createCommand('TRUNCATE TABLE {{%publications_post}} RESTART IDENTITY')
            ->execute();
        Yii::$app->getDb()
            ->createCommand('TRUNCATE TABLE {{%publications_deleted}} RESTART IDENTITY')
            ->execute();
        $this->_repository = new PublicationRepository(Yii::$app->getDb());
    }

    public function testCreateDraftSavesRowWithImageUrls(): void
    {
        $this->_repository->createDraft('Текст черновика', ['https://example.com/a.png'], '2026-09-10 10:00:00');

        $drafts = $this->_repository->allDrafts();

        $this->assertCount(1, $drafts);
        $this->assertSame('Текст черновика', $drafts[0]->text);
        $this->assertSame(['https://example.com/a.png'], $drafts[0]->imageUrls);
        $this->assertNull($drafts[0]->telegramId);
        $this->assertNull($drafts[0]->publishedAt);
        $this->assertSame('2026-09-10 10:00:00', $drafts[0]->createdAt);
    }

    public function testCreatePostSavesScheduledTime(): void
    {
        $this->_repository->createPost('Текст поста', [], '2026-09-10 21:30:00', '2026-09-10 10:00:00');

        $posts = $this->_repository->allPosts();

        $this->assertCount(1, $posts);
        $this->assertSame('Текст поста', $posts[0]->text);
        $this->assertSame('2026-09-10 21:30:00', $posts[0]->publishedAt);
        $this->assertNull($posts[0]->telegramId);
    }

    public function testAllPostsSortedByPublishedAtDescending(): void
    {
        $this->_repository->createPost('Поздний', [], '2026-09-10 22:00:00', '2026-09-10 10:00:00');
        $this->_repository->createPost('Ранний', [], '2026-09-10 21:00:00', '2026-09-10 10:00:00');

        $posts = $this->_repository->allPosts();

        $this->assertSame('Поздний', $posts[0]->text);
        $this->assertSame('Ранний', $posts[1]->text);
    }

    public function testAllDraftsSortedByUpdatedAtDescending(): void
    {
        $this->_repository->createDraft('Первый', [], '2026-09-10 10:00:00');
        $this->_repository->createDraft('Второй', [], '2026-09-10 11:00:00');
        $this->_repository->updateDraft(1, 'Первый обновлён', [], '2026-09-10 12:00:00');

        $drafts = $this->_repository->allDrafts();

        $this->assertSame('Первый обновлён', $drafts[0]->text);
        $this->assertSame('Второй', $drafts[1]->text);
    }

    public function testFindDueForPublishingReturnsOnlyDueUnpublished(): void
    {
        $this->_repository->createPost('Прошлое', [], '2026-09-09 12:00:00', '2026-09-09 10:00:00');
        $this->_repository->createPost('Наступившее', [], '2026-09-10 12:00:00', '2026-09-10 10:00:00');
        $this->_repository->createPost('Будущее', [], '2026-09-11 12:00:00', '2026-09-10 10:00:00');

        $due = $this->_repository->findDueForPublishing('2026-09-10 20:00:00');

        $this->assertCount(2, $due);
        $this->assertSame('Прошлое', $due[0]->text);
        $this->assertSame('Наступившее', $due[1]->text);
    }

    public function testFindDueSkipsPostsWithTelegramId(): void
    {
        $this->_repository->createPost('Опубликованный', [], '2026-09-09 12:00:00', '2026-09-09 10:00:00');
        $this->_repository->storeTelegramId(1, 4242, '2026-09-09 12:05:00', '2026-09-09 12:05:30');

        $due = $this->_repository->findDueForPublishing('2026-09-10 20:00:00');

        $this->assertSame([], $due);
        $posts = $this->_repository->allPosts();
        $this->assertSame(4242, $posts[0]->telegramId);
        $this->assertSame(
            '2026-09-09 12:05:00',
            $posts[0]->publishedAt,
            'published_at must be corrected to the actual send time.',
        );
        $this->assertSame('2026-09-09 12:05:30', $posts[0]->updatedAt);
    }

    public function testAllDeletedSortedByUpdatedAtDescending(): void
    {
        $post = new PublicationData(1, 'Пост', [], null, '2026-09-10 21:00:00', '2026-09-10 10:00:00', '2026-09-10 10:00:00');
        $draft = new PublicationData(2, 'Черновик', [], null, null, '2026-09-10 09:00:00', '2026-09-10 09:00:00');
        $this->_repository->insertDeletedWithHistory($post, '2026-09-10 12:00:00');
        $this->_repository->insertDeletedWithHistory($draft, '2026-09-10 13:00:00');

        $deleted = $this->_repository->allDeleted();

        $this->assertCount(2, $deleted);
        $this->assertSame('Черновик', $deleted[0]->text);
        $this->assertNull($deleted[0]->publishedAt);
        $this->assertNull($deleted[0]->deletedAt);
        $this->assertSame('2026-09-10 13:00:00', $deleted[0]->updatedAt);
        $this->assertSame('Пост', $deleted[1]->text);
        $this->assertSame('2026-09-10 21:00:00', $deleted[1]->publishedAt);
    }

    public function testDeleteDeletedReturnsRecordAndRemovesRow(): void
    {
        $post = new PublicationData(1, 'Пост', [], 4242, '2026-09-10 21:00:00', '2026-09-10 10:00:00', '2026-09-10 10:00:00');
        $this->_repository->insertDeletedWithHistory($post, '2026-09-10 12:00:00');

        $record = $this->_repository->deleteDeleted(1);

        $this->assertSame('Пост', $record->text);
        $this->assertSame(4242, $record->telegramId);
        $this->assertSame('2026-09-10 10:00:00', $record->createdAt);
        $this->assertSame([], $this->_repository->allDeleted());
    }

    public function testDeleteDeletedThrowsWhenRecordNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->_repository->deleteDeleted(999);
    }

    public function testRestoredDeletedRecordMovesToPosts(): void
    {
        $post = new PublicationData(1, 'Восстанавливаемый', [], 4242, '2026-09-10 21:00:00', '2026-09-10 10:00:00', '2026-09-10 10:00:00');
        $this->_repository->insertDeletedWithHistory($post, '2026-09-10 12:00:00');

        $record = $this->_repository->deleteDeleted(1);
        $this->_repository->insertPostWithHistory(
            new PublicationData(
                $record->id,
                $record->text,
                $record->imageUrls,
                null,
                '2026-09-11 12:00:00',
                $record->createdAt,
                $record->updatedAt,
            ),
            '2026-09-11 12:00:00',
        );

        $posts = $this->_repository->allPosts();
        $this->assertCount(1, $posts);
        $this->assertSame('Восстанавливаемый', $posts[0]->text);
        $this->assertNull($posts[0]->telegramId);
        $this->assertSame('2026-09-11 12:00:00', $posts[0]->publishedAt);
        $this->assertSame('2026-09-10 10:00:00', $posts[0]->createdAt);
    }

    public function testRestoredDeletedRecordMovesToDrafts(): void
    {
        $draft = new PublicationData(1, 'Восстанавливаемый черновик', [], null, null, '2026-09-10 09:00:00', '2026-09-10 09:00:00');
        $this->_repository->insertDeletedWithHistory($draft, '2026-09-10 12:00:00');

        $record = $this->_repository->deleteDeleted(1);
        $this->_repository->insertDraftWithHistory($record, '2026-09-11 12:00:00');

        $drafts = $this->_repository->allDrafts();
        $this->assertCount(1, $drafts);
        $this->assertSame('Восстанавливаемый черновик', $drafts[0]->text);
        $this->assertNull($drafts[0]->publishedAt);
        $this->assertSame('2026-09-10 09:00:00', $drafts[0]->createdAt);
    }
}
