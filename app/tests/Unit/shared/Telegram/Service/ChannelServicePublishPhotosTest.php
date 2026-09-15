<?php

declare(strict_types=1);

namespace app\tests\Unit\shared\Telegram\Service;

use app\shared\Telegram\Contract\PublishedDescriptionRepositoryInterface;
use app\shared\Telegram\Contract\TelegramChannelClientInterface;
use app\shared\Telegram\Dto\PostResult;
use app\shared\Telegram\Service\ChannelService;
use Codeception\Test\Unit;
use InvalidArgumentException;

final class ChannelServicePublishPhotosTest extends Unit
{
    private const CHANNEL_ID = '@gsu_travels';

    private TelegramChannelClientInterface&\PHPUnit\Framework\MockObject\MockObject $_client;
    private ChannelService $_service;

    protected function _before(): void
    {
        parent::_before();

        $descriptions = $this->createMock(PublishedDescriptionRepositoryInterface::class);
        $this->_client = $this->createMock(TelegramChannelClientInterface::class);
        $this->_service = new ChannelService($this->_client, self::CHANNEL_ID, $descriptions);
    }

    public function testSinglePhotoIsSentAsPhotoMessageWithCaption(): void
    {
        $this->_client
            ->expects($this->once())
            ->method('sendPhotoMessage')
            ->with(self::CHANNEL_ID, 'https://example.com/a.jpg', 'Текст')
            ->willReturn(new PostResult(11, 123));

        $this->_client->expects($this->never())->method('sendPhotoGroupMessage');
        $this->_client->expects($this->never())->method('sendTextMessage');

        $this->assertSame(11, $this->_service->publishPhotos('Текст', ['https://example.com/a.jpg']));
    }

    public function testTwoToTenPhotosAreSentAsMediaGroup(): void
    {
        $urls = [
            'https://example.com/a.jpg',
            'https://example.com/b.jpg',
            'https://example.com/c.jpg',
        ];

        $this->_client
            ->expects($this->once())
            ->method('sendPhotoGroupMessage')
            ->with(self::CHANNEL_ID, $urls, 'Текст')
            ->willReturn(new PostResult(12, 123));

        $this->_client->expects($this->never())->method('sendPhotoMessage');

        $this->assertSame(12, $this->_service->publishPhotos('Текст', $urls));
    }

    public function testMoreThanTenPhotosAreSplitIntoAlbums(): void
    {
        $urls = [];
        for ($i = 1; $i <= 12; $i++) {
            $urls[] = "https://example.com/{$i}.jpg";
        }

        $this->_client
            ->expects($this->exactly(2))
            ->method('sendPhotoGroupMessage')
            ->willReturnCallback(
                static function (string $channelId, array $photoUrls, string $caption): PostResult {
                    return new PostResult(100 + count($photoUrls), 123);
                },
            );

        $this->assertSame(110, $this->_service->publishPhotos('Текст', $urls));
    }

    public function testLongTextAdditionallySentAsTextMessage(): void
    {
        $text = str_repeat('а', 2000);
        $caption = mb_substr($text, 0, 1024);

        $this->_client
            ->expects($this->once())
            ->method('sendPhotoMessage')
            ->with(self::CHANNEL_ID, 'https://example.com/a.jpg', $caption)
            ->willReturn(new PostResult(13, 123));
        $this->_client
            ->expects($this->once())
            ->method('sendTextMessage')
            ->with(self::CHANNEL_ID, $text)
            ->willReturn(new PostResult(14, 123));

        $this->assertSame(13, $this->_service->publishPhotos($text, ['https://example.com/a.jpg']));
    }

    public function testEmptyTextThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->_service->publishPhotos('', ['https://example.com/a.jpg']);
    }

    public function testEmptyPhotoListThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->_service->publishPhotos('Текст', []);
    }

    public function testEmptyPhotoStringsAreFiltered(): void
    {
        $this->_client
            ->expects($this->once())
            ->method('sendPhotoMessage')
            ->with(self::CHANNEL_ID, 'https://example.com/a.jpg', 'Текст')
            ->willReturn(new PostResult(15, 123));

        $this->assertSame(
            15,
            $this->_service->publishPhotos('Текст', ['', 'https://example.com/a.jpg', '']),
        );
    }
}
