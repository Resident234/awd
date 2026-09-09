<?php

declare(strict_types=1);

namespace app\tests\Unit\shared\Telegram\Infrastructure;

use app\shared\Telegram\Dto\PublishedDescriptionData;
use app\shared\Telegram\Infrastructure\PublishedDescriptionRepository;
use Codeception\Test\Unit;
use Yii;

final class PublishedDescriptionRepositoryTest extends Unit
{
    private PublishedDescriptionRepository $_repository;

    protected function _before(): void
    {
        parent::_before();

        Yii::$app->getDb()
            ->createCommand('TRUNCATE TABLE {{%published_description}}')
            ->execute();
        $this->_repository = new PublishedDescriptionRepository(Yii::$app->getDb());
    }

    public function testArchiveAndStartClosesPreviousRowAndInsertsNewOne(): void
    {
        $this->_repository->archiveAndStart('Первое описание', '2026-09-09 10:00:00');
        $this->_repository->archiveAndStart('Второе описание', '2026-09-09 12:00:00');

        $rows = $this->_repository->all();

        $this->assertCount(2, $rows);
        $this->assertSame('Второе описание', $rows[0]->description);
        $this->assertSame('2026-09-09 12:00:00', $rows[0]->publishedFrom);
        $this->assertNull($rows[0]->publishedTo, 'The newest description must have an empty end date.');
        $this->assertSame('Первое описание', $rows[1]->description);
        $this->assertSame('2026-09-09 10:00:00', $rows[1]->publishedFrom);
        $this->assertSame(
            '2026-09-09 12:00:00',
            $rows[1]->publishedTo,
            'The previous description must be closed with the update time.',
        );
    }

    public function testNextPublishedFromEqualsPreviousPublishedTo(): void
    {
        $this->_repository->archiveAndStart('Первое', '2026-09-09 10:00:00');
        $this->_repository->archiveAndStart('Второе', '2026-09-09 12:00:00');

        [$current, $previous] = $this->_repository->all();

        $this->assertSame(
            $previous->publishedTo,
            $current->publishedFrom,
            'The next published_from must equal the previous published_to.',
        );
    }

    public function testFirstArchiveStartHasNoPreviousRow(): void
    {
        $this->_repository->archiveAndStart('Первое', '2026-09-09 10:00:00');

        $rows = $this->_repository->all();

        $this->assertCount(1, $rows);
        $this->assertInstanceOf(PublishedDescriptionData::class, $rows[0]);
        $this->assertNull($rows[0]->publishedTo);
        $this->assertSame('2026-09-09 10:00:00', $rows[0]->publishedFrom);
    }

    public function testAllOrdersNewestFirst(): void
    {
        $this->_repository->archiveAndStart('Первое', '2026-09-09 10:00:00');
        $this->_repository->archiveAndStart('Второе', '2026-09-09 12:00:00');
        $this->_repository->archiveAndStart('Третье', '2026-09-09 14:00:00');

        $rows = $this->_repository->all();

        $this->assertCount(3, $rows);
        $this->assertSame('Третье', $rows[0]->description);
        $this->assertSame('Второе', $rows[1]->description);
        $this->assertSame('Первое', $rows[2]->description);
        $this->assertTrue($rows[0]->id > $rows[1]->id && $rows[1]->id > $rows[2]->id);
    }
}
