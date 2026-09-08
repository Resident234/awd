<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\shared\Forum\Contract\ForumHttpClientInterface;
use app\shared\Forum\Contract\ForumRepositoryInterface;
use app\shared\Forum\Dto\MemberData;
use app\shared\Forum\Service\MemberProfilePageParser;
use app\shared\Forum\Service\MemberScanService;
use Codeception\Test\Unit;
use Psr\Log\NullLogger;

final class MemberScanServiceTest extends Unit
{
    private const CONFIG = [
        'id' => '1',
        'code' => 'awd_forum_members',
        'base_url' => 'https://forum.awd.ru/memberlist.php?mode=viewprofile&u=',
        't_from' => '125071',
        't_to' => '125073',
        'is_active' => '1',
    ];

    private const MAIN_HTML = <<<'HTML'
<html><body>
<div id="page-body">
<h2>Профиль пользователя SergeiKa</h2>
<dl class="left-box"><dt><img src='./styles/prosilver/theme/images/default_avatar.gif' /></dt><dd>путешественник</dd></dl>
<dl class="left-box details">
	<dt>Имя пользователя:</dt> <dd><span>SergeiKa</span></dd>
	<dt>Страна/территория:</dt> <dd>Россия</dd><dt>Город:</dt> <dd>Москва</dd><dt>Возраст:</dt> <dd>50</dd><dt>Пол:</dt> <dd>Мужской</dd>
</dl>
</div>
</body></html>
HTML;

    private const STATS_HTML = <<<'HTML'
<html><body>
<div id="page-body">
<div class="panel bg2"><div class="inner"><div>
<h3>Статистика пользователя</h3>
<dl class="details">
	<dt>Зарегистрирован:</dt> <dd>24 дек 2011, 22:42</dd>
	<dt>Последнее посещение:</dt> <dd>27 авг 2026, 20:10</dd>
	<dt>Всего сообщений:</dt> <dd>1395 | <strong><a href="./search.php?author_id=125072">link</a></strong> (0.01%)</dd>
	<dt>Фотографий:</dt> <dd>0 | <strong><a href="./gallery/search.php?user_id=125072">link</a></strong></dd>
</dl>
</div></div></div>
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

    private function createService(): MemberScanService
    {
        return new MemberScanService(
            $this->_repository,
            $this->_httpClient,
            new MemberProfilePageParser(),
            new NullLogger(),
        );
    }

    public function testRunSavesMembersAndSkipsFailures(): void
    {
        $this->_repository->method('activeConfig')->willReturn(self::CONFIG);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('saveMemberProfile')->willReturn(true, false);
        $this->_repository->expects($this->once())->method('markRun');
        $this->_repository->expects($this->once())->method('releaseLock');

        $this->_httpClient->method('get')->willReturnCallback(
            static function (string $url): string {
                if (str_contains($url, 'u=125072')) {
                    throw new \RuntimeException('unexpected ' . $url);
                }
                if (str_contains($url, 'page=7')) {
                    return self::STATS_HTML;
                }
                if (str_contains($url, 'u=125071') || str_contains($url, 'u=125073')) {
                    return self::MAIN_HTML;
                }
                throw new \RuntimeException('unexpected ' . $url);
            },
        );

        $stats = $this->createService()->run(null, null, null);

        $this->assertSame(3, $stats['processed']);
        $this->assertSame(1, $stats['saved']);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame(0, $stats['not_found']);
        $this->assertSame(1, $stats['failed']);
    }

    public function testRunMergesMainAndStatistics(): void
    {
        $this->_repository->method('activeConfig')->willReturn(
            array_merge(self::CONFIG, ['t_from' => '125072', 't_to' => '125072'])
        );
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('saveMemberProfile')->willReturn(true);
        $this->_repository->expects($this->once())->method('saveMemberProfile')->with(
            $this->callback(static function (MemberData $member): bool {
                return $member->id === 125072
                    && $member->name === 'SergeiKa'
                    && $member->rankName === 'путешественник'
                    && $member->city === 'Москва'
                    && $member->age === 50
                    && $member->gender === 'Мужской'
                    && $member->messagesCount === 1395
                    && $member->photosCount === 0
                    && $member->lastVisitAt === '2026-08-27 20:10:00'
                    && $member->registeredOn === '2011-12-24';
            }),
            $this->anything(),
        );

        $this->_httpClient->method('get')->willReturnCallback(
            static function (string $url): string {
                return str_contains($url, 'page=7') ? self::STATS_HTML : self::MAIN_HTML;
            },
        );

        $stats = $this->createService()->run(null, null, null);
        $this->assertSame(1, $stats['processed']);
        $this->assertSame(1, $stats['saved']);
        $this->assertSame(0, $stats['failed']);
    }

    public function testRunSavesLoginRequiredStub(): void
    {
        $this->_repository->method('activeConfig')->willReturn(
            array_merge(self::CONFIG, ['t_from' => '125075', 't_to' => '125075'])
        );
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('saveMemberProfile')->willReturn(true);
        $this->_repository->expects($this->once())->method('saveMemberProfile')->with(
            $this->callback(static function (MemberData $member): bool {
                return $member->id === 125075
                    && $member->profileUrl === 'https://forum.awd.ru/memberlist.php?mode=viewprofile&u=125075'
                    && $member->profileLoginRequired === true;
            }),
        );

        $this->_httpClient->method('get')->willReturn(
            '<html><body><h2>Для просмотра профилей вы должны быть авторизованы.</h2></body></html>',
        );

        $stats = $this->createService()->run(null, null, null);
        $this->assertSame(1, $stats['processed']);
        $this->assertSame(1, $stats['login_required']);
        $this->assertSame(1, $stats['saved']);
        $this->assertSame(0, $stats['failed']);
    }

    public function testRunRespectsLimit(): void
    {
        $this->_repository->method('activeConfig')->willReturn(self::CONFIG);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('saveMemberProfile')->willReturn(true);
        $this->_httpClient->method('get')->willReturnCallback(
            static function (string $url): string {
                return str_contains($url, 'page=7') ? self::STATS_HTML : self::MAIN_HTML;
            },
        );

        $stats = $this->createService()->run(null, null, 1);
        $this->assertSame(1, $stats['processed']);
        $this->assertSame(1, $stats['saved']);
    }

    public function testRunSkipsWhenLockIsHeld(): void
    {
        $this->_repository->method('acquireLock')->willReturn(false);
        $this->_repository->expects($this->never())->method('saveMemberProfile');
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
