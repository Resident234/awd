<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\shared\Forum\Contract\ForumHttpClientInterface;
use app\shared\Forum\Contract\ForumRepositoryInterface;
use app\shared\Forum\Service\ForumPostPageParser;
use app\shared\Forum\Service\ForumPostScanService;
use Codeception\Test\Unit;
use Psr\Log\NullLogger;

final class ForumPostScanServiceTest extends Unit
{
    private const CONFIG = [
        'id' => '2',
        'code' => 'awd_forum_posts',
        'base_url' => 'https://forum.awd.ru/viewtopic.php?t=',
        't_from' => '415948',
        't_to' => '415949',
        'is_active' => '1',
    ];

    private const PAGE_1 = <<<'HTML'
<html><body>
<h2 class="topic-title"><a href="./viewtopic.php?t=415949">Freedom Finance</a></h2>
<div class="pagination">Сообщений: 2 &bull; <span><strong>1</strong><span class="page-sep">, </span><a href="./viewtopic.php?t=415949&amp;sid=x&amp;start=1">2</a></span></div>
<div id="p1" class="post bg1"><div class="postbody">
    <h3><a href="#p1">Freedom Finance</a></h3>
    <div style="float: right;">Сообщение: <a href="./viewtopic.php?p=1#p1">#1</a></div>
    <p class="author"><strong><a href="./memberlist.php?mode=viewprofile&amp;u=1">author1</a></strong> &raquo; 20 сен 2022, 13:49 </p>
    <div class="content">Первое сообщение</div>
</div>
<dl class="postprofile"><dt><a href="./memberlist.php?mode=viewprofile&amp;u=1">author1</a></dt><dd>новичок</dd><dd><strong>Сообщения:</strong> 10</dd></dl></div>
</body></html>
HTML;

    private const PAGE_2 = <<<'HTML'
<html><body>
<h2 class="topic-title"><a href="./viewtopic.php?t=415949">Freedom Finance</a></h2>
<div class="pagination">Сообщений: 2 &bull; <span><a href="./viewtopic.php?t=415949&amp;sid=x">1</a><span class="page-sep">, </span><strong>2</strong></span></div>
<div id="p1" class="post bg1"><div class="postbody">
    <h3><a href="#p1">Freedom Finance</a></h3>
    <div style="float: right;">Сообщение: <a href="./viewtopic.php?p=1#p1">#2</a></div>
    <p class="author"><strong><a href="./memberlist.php?mode=viewprofile&amp;u=1">author1</a></strong> &raquo; 20 сен 2022, 13:49 </p>
    <div class="content">Первое сообщение (повтор в начале страницы)</div>
</div>
<dl class="postprofile"><dt><a href="./memberlist.php?mode=viewprofile&amp;u=1">author1</a></dt><dd>новичок</dd><dd><strong>Сообщения:</strong> 10</dd></dl></div>
<div id="p2" class="post bg2"><div class="postbody">
    <h3><a href="#p2">Re: Freedom Finance</a></h3>
    <div style="float: right;">Сообщение: <a href="./viewtopic.php?p=2#p2">#3</a></div>
    <p class="author"><strong><a href="./memberlist.php?mode=viewprofile&amp;u=2">author2</a></strong> &raquo; 21 сен 2022, 10:00 </p>
    <div class="content">Второе сообщение</div>
</div>
<dl class="postprofile"><dt><a href="./memberlist.php?mode=viewprofile&amp;u=2">author2</a></dt><dd>участник</dd><dd><strong>Сообщения:</strong> 20</dd></dl></div>
</body></html>
HTML;

    private ForumRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $_repository;
    private ForumHttpClientInterface&\PHPUnit\Framework\MockObject\MockObject $_httpClient;

    protected function _before(): void
    {
        $this->_repository = $this->createMock(ForumRepositoryInterface::class);
        $this->_httpClient = $this->createMock(ForumHttpClientInterface::class);
    }

    private function createService(): ForumPostScanService
    {
        return new ForumPostScanService(
            $this->_repository,
            $this->_httpClient,
            new ForumPostPageParser(),
            new NullLogger(),
        );
    }

    public function testWalksAllTopicPagesAndSavesPosts(): void
    {
        $this->_repository->method('activeConfig')->willReturn(self::CONFIG);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('existingTopicIds')->willReturn([415949]);
        $this->_repository->method('savePosts')->willReturnCallback(
            static fn (array $posts): int => count($posts),
        );
        $this->_repository->expects($this->once())->method('markRun');
        $this->_repository->expects($this->once())->method('releaseLock');

        $this->_httpClient->method('get')->willReturnCallback(
            static function (string $url): string {
                return match ($url) {
                    'https://forum.awd.ru/viewtopic.php?t=415949' => self::PAGE_1,
                    'https://forum.awd.ru/viewtopic.php?t=415949&start=1' => self::PAGE_2,
                    default => throw new \RuntimeException('unexpected ' . $url),
                };
            },
        );

        $stats = $this->createService()->run(null, null, null);

        $this->assertSame(1, $stats['processed']);
        $this->assertSame(2, $stats['pages']);
        $this->assertSame(2, $stats['posts_saved']);
        $this->assertSame(0, $stats['posts_updated']);
        $this->assertSame(0, $stats['topics_failed']);
    }

    public function testUpdatesExistingPosts(): void
    {
        $this->_repository->method('activeConfig')->willReturn(self::CONFIG);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('existingTopicIds')->willReturn([415949]);
        $this->_repository->method('savePosts')->willReturn(0);

        $this->_httpClient->method('get')->willReturn(self::PAGE_1);

        $stats = $this->createService()->run(null, null, null, 1);
        $this->assertSame(1, $stats['pages']);
        $this->assertSame(0, $stats['posts_saved']);
        $this->assertSame(1, $stats['posts_updated']);
    }

    public function testCountsLoginRequiredTopic(): void
    {
        $this->_repository->method('activeConfig')->willReturn(self::CONFIG);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('existingTopicIds')->willReturn([415949]);
        $this->_httpClient->method('get')->willReturn('<html><body>Для просмотра этого форума вы должны быть авторизованы</body></html>');

        $stats = $this->createService()->run(null, null, null);
        $this->assertSame(1, $stats['topics_login_required']);
        $this->assertSame(0, $stats['topics_failed']);
    }

    public function testCountsFailedTopic(): void
    {
        $this->_repository->method('activeConfig')->willReturn(self::CONFIG);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('existingTopicIds')->willReturn([415949]);
        $this->_httpClient->method('get')->willReturn('<html><body>broken</body></html>');

        $stats = $this->createService()->run(null, null, null);
        $this->assertSame(1, $stats['topics_skipped_no_posts']);
        $this->assertSame(0, $stats['topics_failed']);
    }

    public function testRespectsTopicLimit(): void
    {
        $this->_repository->method('activeConfig')->willReturn(self::CONFIG);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('existingTopicIds')->willReturn([415948, 415949]);
        $this->_repository->method('savePosts')->willReturn(0);

        $this->_httpClient->method('get')->willReturn(self::PAGE_1);

        $stats = $this->createService()->run(null, null, 1);
        $this->assertSame(1, $stats['processed']);
    }

    public function testSkipsWhenLockIsHeld(): void
    {
        $this->_repository->method('acquireLock')->willReturn(false);
        $this->_repository->expects($this->never())->method('savePosts');

        $stats = $this->createService()->run();
        $this->assertNull($stats);
    }

    public function testDoesNothingWithoutConfig(): void
    {
        $this->_repository->method('activeConfig')->willReturn(null);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->expects($this->never())->method('markRun');

        $stats = $this->createService()->run();
        $this->assertSame(0, $stats['processed']);
    }
}
