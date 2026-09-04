<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\shared\Forum\Service\ForumHtmlParser;
use Codeception\Test\Unit;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class ForumHtmlParserTest extends Unit
{
    private const TOPIC_HTML = <<<'HTML'
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru">
<head><title>Малоярославец. Май 2026. Город белый, город голубой</title></head>
<body id="phpbb" class="section-viewtopic ltr">
<div id="wrap">
<div id="page-body">
<h2 class="topic-title"><a href="./viewtopic.php?t=441019">Малоярославец. Май 2026. Город белый, город голубой</a></h2>
<div id="p12406136" class="post bg2">
    <div class="postbody" id="pb12406136">
        <h3 class="first"><a href="#p12406136">Малоярославец. Май 2026. Город белый, город голубой</a></h3>
        <p class="author">msg-icon <strong><a href="./memberlist.php?mode=viewprofile&amp;u=23071&amp;sid=x">Jo</a></strong> &raquo; 27 авг 2026, 19:42 </p>
        <div class="content" id="pc12406136">
            <img class="lazyload" data-src="https://live.staticflickr.com/65535/55491522951_2a109d663b_b.jpg" alt="pic" />
            <noscript><img src="https://live.staticflickr.com/65535/55491522951_2a109d663b_b.jpg" alt="pic" /></noscript><br /><br />
            Давно смотрел на этот городок.<br /><br />
            <img class="lazyload" data-src="https://live.staticflickr.com/65535/55491639958_6b969b2f49_c.jpg" alt="pic2" />
        </div>
    </div>
    <dl class="postprofile" id="profile12406136">
        <dt>
            <a href="./memberlist.php?mode=viewprofile&amp;u=23071&amp;sid=x"><img src="/./images/avatars/upload/16e329dc891e1553c071fa5fe2d52d91_23071.jpg" alt="Аватара пользователя" /></a><br />
            <a href="./memberlist.php?mode=viewprofile&amp;u=23071&amp;sid=x">Jo</a>
        </dt>
        <dd>новичок</dd>
        <dd>&nbsp;</dd>
        <dd><strong>Сообщения:</strong> 37</dd><dd><strong>Регистрация:</strong> 10.07.2007</dd><dd><strong>Город:</strong> Москва</dd>
        <dd><strong>Благодарил&nbsp;(а):</strong> 0 раз.</dd>
        <dd><strong>Поблагодарили:</strong> <a href="./thankslist.php?mode=givens&amp;author_id=23071&amp;give=false">34</a> раз.</dd>
        <dd><strong>Возраст:</strong> 50 </dd>
        <dd><strong>Страны:</strong> <a href="./memberlist.php?mode=viewprofile&amp;u=23071&amp;page=1" title="Страны">26</a></dd>
        <dd><strong>Отчеты:</strong> <a href="./memberlist.php?mode=viewprofile&amp;u=23071&amp;page=0" title="Отчеты">9</a></dd>
        <dd><strong>Пол:</strong> Мужской</dd>
    </dl>
</div>
</div>
</div>
</body>
</html>
HTML;

    public function testParsesTopicWithAuthorAndImages(): void
    {
        $parser = new ForumHtmlParser();
        $topic = $parser->parse(
            441019,
            'https://forum.awd.ru/viewtopic.php?t=441019',
            self::TOPIC_HTML,
            new DateTimeImmutable('2026-09-03 12:00:00', new DateTimeZone('UTC')),
        );

        $this->assertSame(441019, $topic->id);
        $this->assertSame('https://forum.awd.ru/viewtopic.php?t=441019', $topic->sourceUrl);
        $this->assertSame('Малоярославец. Май 2026. Город белый, город голубой', $topic->title);
        $this->assertSame('2026-08-27 19:42:00', $topic->publishedAt);
        $this->assertStringContainsString('Давно смотрел на этот городок.', $topic->contentText);
        $this->assertSame(
            [
                'https://live.staticflickr.com/65535/55491522951_2a109d663b_b.jpg',
                'https://live.staticflickr.com/65535/55491639958_6b969b2f49_c.jpg',
            ],
            $topic->imageUrls,
        );

        $author = $topic->author;
        $this->assertNotNull($author);
        $this->assertSame(23071, $author->id);
        $this->assertSame('Jo', $author->name);
        $this->assertStringContainsString('memberlist.php?mode=viewprofile&u=23071', $author->profileUrl);
        $this->assertStringContainsString('images/avatars/upload/16e329dc891e1553c071fa5fe2d52d91_23071.jpg', (string)$author->avatarUrl);
        $this->assertSame('новичок', $author->rankName);
        $this->assertSame(37, $author->messagesCount);
        $this->assertSame('2007-07-10', $author->registeredOn);
        $this->assertSame('Москва', $author->city);
        $this->assertSame(0, $author->thanksGivenCount);
        $this->assertSame(34, $author->thanksReceivedCount);
        $this->assertSame(50, $author->age);
        $this->assertSame(26, $author->countriesCount);
        $this->assertSame(9, $author->reportsCount);
        $this->assertSame('Мужской', $author->gender);
    }

    public function testNormalizesRelativeDates(): void
    {
        $parser = new ForumHtmlParser();
        $html = str_replace('27 авг 2026, 19:42', 'Вчера, 19:42', self::TOPIC_HTML);
        $topic = $parser->parse(
            441019,
            'https://forum.awd.ru/viewtopic.php?t=441019',
            $html,
            new DateTimeImmutable('2026-09-03 12:00:00', new DateTimeZone('UTC')),
        );
        $this->assertSame('2026-09-02 19:42:00', $topic->publishedAt);
    }

    public function testNormalizesTodayDates(): void
    {
        $parser = new ForumHtmlParser();
        $html = str_replace('27 авг 2026, 19:42', 'Сегодня, 09:05', self::TOPIC_HTML);
        $topic = $parser->parse(
            441019,
            'https://forum.awd.ru/viewtopic.php?t=441019',
            $html,
            new DateTimeImmutable('2026-09-03 12:00:00', new DateTimeZone('UTC')),
        );
        $this->assertSame('2026-09-03 09:05:00', $topic->publishedAt);
    }

    public function testThrowsOnInvalidHtml(): void
    {
        $parser = new ForumHtmlParser();
        $this->expectException(RuntimeException::class);
        $parser->parse(
            1,
            'https://forum.awd.ru/viewtopic.php?t=1',
            '<html><body><p>No topic here</p></body></html>',
        );
    }
}
