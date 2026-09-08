<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\shared\Forum\Infrastructure\ForumLoginRequiredException;
use app\shared\Forum\Infrastructure\ForumPageNotFoundException;
use app\shared\Forum\Service\MemberProfilePageParser;
use Codeception\Test\Unit;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class MemberProfilePageParserTest extends Unit
{
    private const PROFILE_URL = 'https://forum.awd.ru/memberlist.php?mode=viewprofile&u=125072';

    private const MAIN_HTML = <<<'HTML'
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru">
<body id="phpbb" class="section-memberlist ltr">
<div id="wrap">
<a name="start_here"></a>
<div id="page-body">
<h2>Профиль пользователя SergeiKa</h2>
<form method="post" action="./memberlist.php?mode=group" id="viewprofile">
	<div class="panel bg1">
		<div class="inner">
			<dl class="left-box">
			<dt><img src='./styles/prosilver/theme/images/default_avatar.gif' alt='' /></dt>
				<dd style="text-align: center;">путешественник</dd>
			</dl>
			<dl class="left-box details" style="width: 80%;">
				<dt>Имя пользователя:</dt>
				<dd>
					<span>SergeiKa</span>
				</dd>
				<dt>Страна/территория:</dt> <dd>Россия</dd><dt>Город:</dt> <dd>Москва</dd><dt>Возраст:</dt> <dd>50</dd><dt>Пол:</dt> <dd>Мужской</dd>
			</dl>
		</div>
	</div>
</form>
</div>
</div>
</body>
</html>
HTML;

    private const STATS_HTML = <<<'HTML'
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru">
<body id="phpbb" class="section-memberlist ltr">
<div id="wrap">
<a name="start_here"></a>
<div id="page-body">
<h2>Профиль пользователя SergeiKa</h2>
<form method="post" action="./memberlist.php?mode=group" id="viewprofile">
	<div class="panel bg2">
		<div class="inner">
			<div>
				<h3>Статистика пользователя</h3>
				<dl class="details">
					<dt>Зарегистрирован:</dt> <dd>24 дек 2011, 22:42</dd>
					<dt>Последнее посещение:</dt> <dd>Сегодня, 17:14</dd>
					<dt>Всего сообщений:</dt>
					<dd>1395 | <strong><a href="./search.php?author_id=125072&amp;sr=posts">Найти сообщения пользователя</a></strong>&nbsp;(0.01% всех сообщений / 0.26 сообщений в день)
					</dd>
					<dt>Фотографий:</dt>
					<dd>0 | <strong><a href="./gallery/search.php?user_id=125072">Найти все фото пользователя</a></strong>
					</dd>
				</dl>
			</div>
		</div>
	</div>
</form>
</div>
</div>
</body>
</html>
HTML;

    public function testParsesMainProfilePage(): void
    {
        $parser = new MemberProfilePageParser();
        $member = $parser->parseMain(125072, self::PROFILE_URL, self::MAIN_HTML);

        $this->assertSame(125072, $member->id);
        $this->assertSame(self::PROFILE_URL, $member->profileUrl);
        $this->assertSame('SergeiKa', $member->name);
        $this->assertSame('путешественник', $member->rankName);
        $this->assertSame(
            'https://forum.awd.ru/styles/prosilver/theme/images/default_avatar.gif',
            $member->avatarUrl,
        );
        $this->assertSame('Москва', $member->city);
        $this->assertSame(50, $member->age);
        $this->assertSame('Мужской', $member->gender);
        $this->assertSame('Россия', $member->rawData['Страна/территория']);
    }

    public function testParsesStatisticsTab(): void
    {
        $parser = new MemberProfilePageParser();
        $stats = $parser->parseStatistics(
            self::PROFILE_URL . '&page=7',
            self::STATS_HTML,
            new DateTimeImmutable('2026-09-07 20:00:00', new DateTimeZone('UTC')),
        );

        $this->assertSame('2011-12-24 22:42:00', $stats['registered_at']);
        $this->assertSame('2026-09-07 17:14:00', $stats['last_visit_at']);
        $this->assertSame(1395, $stats['messages_count']);
        $this->assertSame(0, $stats['photos_count']);
    }

    public function testNormalizesYesterdayLastVisit(): void
    {
        $parser = new MemberProfilePageParser();
        $html = str_replace('Сегодня, 17:14', 'Вчера, 17:14', self::STATS_HTML);
        $stats = $parser->parseStatistics(
            self::PROFILE_URL . '&page=7',
            $html,
            new DateTimeImmutable('2026-09-07 20:00:00', new DateTimeZone('UTC')),
        );
        $this->assertSame('2026-09-06 17:14:00', $stats['last_visit_at']);
    }

    public function testNormalizesRelativeLastVisit(): void
    {
        $parser = new MemberProfilePageParser();
        $html = str_replace('Сегодня, 17:14', '2 минуты назад', self::STATS_HTML);
        $stats = $parser->parseStatistics(
            self::PROFILE_URL . '&page=7',
            $html,
            new DateTimeImmutable('2026-09-07 20:00:00', new DateTimeZone('UTC')),
        );
        // 20:00 UTC = 23:00 site time; minus 2 minutes = 22:58
        $this->assertSame('2026-09-07 22:58:00', $stats['last_visit_at']);
    }

    public function testNormalizesLessThanMinuteAgoLastVisit(): void
    {
        $parser = new MemberProfilePageParser();
        $html = str_replace('Сегодня, 17:14', 'менее минуты назад', self::STATS_HTML);
        $stats = $parser->parseStatistics(
            self::PROFILE_URL . '&page=7',
            $html,
            new DateTimeImmutable('2026-09-07 20:00:00', new DateTimeZone('UTC')),
        );
        $this->assertSame('2026-09-07 23:00:00', $stats['last_visit_at']);
    }

    public function testThrowsOnMissingMember(): void
    {
        $parser = new MemberProfilePageParser();
        $this->expectException(ForumPageNotFoundException::class);
        $parser->parseMain(
            499999999,
            'https://forum.awd.ru/memberlist.php?mode=viewprofile&u=499999999',
            '<html><body><p>Запрашиваемого пользователя не существует.</p></body></html>',
        );
    }

    public function testThrowsOnLoginRequired(): void
    {
        $parser = new MemberProfilePageParser();
        $this->expectException(ForumLoginRequiredException::class);
        $parser->parseMain(
            125072,
            self::PROFILE_URL,
            '<html><body><h2>Для просмотра профилей вы должны быть авторизованы.</h2></body></html>',
        );
    }

    public function testThrowsOnInvalidHtml(): void
    {
        $parser = new MemberProfilePageParser();
        $this->expectException(RuntimeException::class);
        $parser->parseMain(1, 'https://forum.awd.ru/memberlist.php?mode=viewprofile&u=1', '<html><body><p>No profile</p></body></html>');
    }
}
