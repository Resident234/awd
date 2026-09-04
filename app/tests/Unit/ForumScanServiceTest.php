<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\shared\Forum\Contract\ForumHttpClientInterface;
use app\shared\Forum\Contract\ForumRepositoryInterface;
use app\shared\Forum\Service\ForumHtmlParser;
use app\shared\Forum\Service\ForumScanService;
use Codeception\Test\Unit;
use Psr\Log\NullLogger;

final class ForumScanServiceTest extends Unit
{
    private const CONFIG = [
        'id' => '1',
        'code' => 'awd_forum_topics',
        'base_url' => 'https://forum.awd.ru/viewtopic.php?t=',
        't_from' => '441018',
        't_to' => '441020',
        'is_active' => '1',
    ];

    private const TOPIC_HTML = <<<'HTML'
<html><body>
<h2 class="topic-title"><a href="./viewtopic.php?t=441018">Title</a></h2>
<div class="post">
  <div class="postbody">
    <p class="author"><strong><a href="./memberlist.php?mode=viewprofile&amp;u=23071">Jo</a></strong> &raquo; 27 авг 2026, 19:42</p>
    <div class="content">Hello world</div>
  </div>
  <dl class="postprofile"><dt><a href="./memberlist.php?mode=viewprofile&amp;u=23071">Jo</a></dt>
  <dd>новичок</dd><dd><strong>Сообщения:</strong> 37</dd></dl>
</div>
</body></html>
HTML;

    private ForumRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $_repository;
    private ForumHttpClientInterface&\PHPUnit\Framework\MockObject\MockObject $_httpClient;

    protected function _before(): void
    {
        $this->_repository = $this->createMock(ForumRepositoryInterface::class);
        $this->_httpClient = $this->createMock(ForumHttpClientInterface::class);
    }

    private function createService(): ForumScanService
    {
        return new ForumScanService(
            $this->_repository,
            $this->_httpClient,
            new ForumHtmlParser(),
            new NullLogger(),
        );
    }

    public function testRunSavesTopicsAndSkipsFailures(): void
    {
        $this->_repository->method('activeConfig')->willReturn(self::CONFIG);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('save')->willReturn(true, false);
        $this->_repository->expects($this->once())->method('markRun');
        $this->_repository->expects($this->once())->method('releaseLock');

        $this->_httpClient->method('get')->willReturnCallback(
            static function (string $url): string {
                return match (true) {
                    str_ends_with($url, '=441018'), str_ends_with($url, '=441020') => self::TOPIC_HTML,
                    str_ends_with($url, '=441019') => '<html><body>404</body></html>',
                    default => throw new \RuntimeException('unexpected ' . $url),
                };
            },
        );

        $stats = $this->createService()->run(null, null, null);

        $this->assertSame(3, $stats['processed']);
        $this->assertSame(1, $stats['saved']);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame(0, $stats['not_found']);
        $this->assertSame(1, $stats['failed']);
    }

    public function testRunCountsLoginRequiredTopics(): void
    {
        $config = array_merge(self::CONFIG, ['t_from' => '441021', 't_to' => '441021']);
        $this->_repository->method('activeConfig')->willReturn($config);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_httpClient->method('get')->willReturn(
            '<html><body>Для просмотра этого форума вы должны быть авторизованы</body></html>',
        );

        $stats = $this->createService()->run(null, null, null);
        $this->assertSame(1, $stats['processed']);
        $this->assertSame(1, $stats['login_required']);
        $this->assertSame(0, $stats['failed']);
    }

    public function testRunRespectsLimit(): void
    {
        $this->_repository->method('activeConfig')->willReturn(self::CONFIG);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('save')->willReturn(true);
        $this->_httpClient->method('get')->willReturn(self::TOPIC_HTML);

        $stats = $this->createService()->run(null, null, 1);
        $this->assertSame(1, $stats['processed']);
        $this->assertSame(1, $stats['saved']);
    }

    public function testRunSkipsWhenLockIsHeld(): void
    {
        $this->_repository->method('acquireLock')->willReturn(false);
        $this->_repository->expects($this->never())->method('save');
        $this->_repository->expects($this->never())->method('markRun');
        $this->_repository->expects($this->never())->method('releaseLock');

        $stats = $this->createService()->run();
        $this->assertNull($stats);
    }

    public function testRunDoesNothingWithoutConfig(): void
    {
        $this->_repository->method('activeConfig')->willReturn(null);
        $this->_repository->method('acquireLock')->willReturn(true);

        $stats = $this->createService()->run();
        $this->assertSame(0, $stats['processed']);
        $this->assertSame(0, $stats['saved']);
    }
}
