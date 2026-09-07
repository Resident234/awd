<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\shared\Forum\Service\ForumPostPageParser;
use Codeception\Test\Unit;
use DateTimeImmutable;
use DateTimeZone;

final class ForumPostPageParserTest extends Unit
{
    private const PAGE_HTML = <<<'HTML'
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru">
<head><title>Freedom Finance Казахстан</title></head>
<body id="phpbb" class="section-viewtopic ltr">
<div id="wrap">
<div id="page-body">
<h2 class="topic-title"><a href="./viewtopic.php?t=415949">Freedom Finance Казахстан: переводы денег и работа карт за границей</a></h2>
<div class="pagination">
    Сообщений: 9447 &bull; <a href="#">Страница <strong>2</strong> из <strong>4</strong></a> &bull;
    <span><strong>2</strong><span class="page-sep">, </span><a href="./viewtopic.php?t=415949&amp;sid=abc&amp;start=100">3</a><span class="page-sep">, </span><a href="./viewtopic.php?t=415949&amp;sid=abc&amp;start=150">4</a></span>
</div>

<div id="p11860687" class="post bg1">
    <div class="postbody" id="pb11860687">
        <h3><a href="#p11860687">Re: Freedom Finance Казахстан: переводы денег и работа карт за границей</a></h3>
        <div style="float: right;">Сообщение: <a href="./viewtopic.php?p=11860687&amp;sid=abc#p11860687">#4495</a></div>
        <p class="author"><a href="./viewtopic.php?p=11860687&amp;sid=abc#p11860687"><img src="./styles/prosilver/imageset/icon_post_target.gif" alt="Сообщение" /></a> <strong><a href="#postform" id="poster11860687" title="Вставить имя пользователя" onclick="insert_text('[b]@nnet.[/b], '); return false;">@nnet.</a></strong> &raquo; 04 июл 2024, 17:50 </p>
        <div class="content">Текст первого поста<br />вторая строка</div>
    </div>
    <dl class="postprofile" id="profile11860687">
        <dt>
            <a href="./memberlist.php?mode=viewprofile&amp;u=394702&amp;sid=abc">@nnet.</a>
        </dt>
        <dd>новичок<br><a href="#postform" title="Выделите текст в сообщении">Цитата выделенного текста</a></dd>
        <dd>&nbsp;</dd>
        <dd><strong>Сообщения:</strong> 19</dd><dd><strong>Регистрация:</strong> 11.12.2015</dd><dd><strong>Город:</strong> Ижевск</dd>
        <dd><strong>Благодарил&nbsp;(а):</strong> <a href="./thankslist.php?mode=givens&amp;author_id=394702&amp;give=true">2</a> раз.</dd>
        <dd><strong>Поблагодарили:</strong> 0 раз.</dd>
        <dd><strong>Возраст:</strong> 34 </dd>
        <dd><strong>Пол:</strong> Женский</dd>
        <dd>
            <ul class="profile-icons">
                <li class="photo-icon"><a href="./download/file.php?photo=394702_1469094668.jpg" class="highslide"><span>Фотография пользователя</span></a></li>
            </ul>
        </dd>
    </dl>
</div>

<div id="p11860699" class="post bg2">
    <div class="postbody" id="pb11860699">
        <h3><a href="#p11860699">Re: Freedom Finance Казахстан: переводы денег и работа карт за границей</a></h3>
        <div style="float: right;">Сообщение: <a href="./viewtopic.php?p=11860699&amp;sid=abc#p11860699">#4496</a></div>
        <p class="author"><strong><a href="./memberlist.php?mode=viewprofile&amp;u=811438&amp;sid=abc">user2</a></strong> &raquo; 05 июл 2024, 09:05 </p>
        <div class="content">Ответ от второго автора</div>
    </div>
    <dl class="postprofile" id="profile11860699">
        <dt><a href="./memberlist.php?mode=viewprofile&amp;u=811438&amp;sid=abc"><img src="./images/avatars/upload/nophoto_811438.jpg" alt="Аватара пользователя" /></a><br /><a href="./memberlist.php?mode=viewprofile&amp;u=811438&amp;sid=abc">user2</a></dt>
        <dd>участник</dd>
        <dd><strong>Сообщения:</strong> 55</dd>
    </dl>
</div>

<div class="post bg2" style="margin: 12px 0 18px 0;">
    <div style="padding-left: 230px;">
        <div style="text-align:center;font-weight:bold;">Похожие темы</div>
        <a href="./viewtopic.php?t=999999">Списали деньги с карты Freedom</a>
    </div>
</div>
</div>
</div>
</body>
</html>
HTML;

    public function testParsesAllPostsOnThePage(): void
    {
        $parser = new ForumPostPageParser();
        $posts = $parser->parse(
            415949,
            'https://forum.awd.ru/viewtopic.php?t=415949&start=50',
            self::PAGE_HTML,
            new DateTimeImmutable('2026-09-06 12:00:00', new DateTimeZone('UTC')),
        );

        $this->assertCount(2, $posts);

        $first = $posts[0];
        $this->assertSame(11860687, $first->id);
        $this->assertSame(415949, $first->topicId);
        $this->assertSame(4495, $first->number);
        $this->assertSame('Re: Freedom Finance Казахстан: переводы денег и работа карт за границей', $first->title);
        $this->assertSame('2024-07-04 17:50:00', $first->postedAt);
        $this->assertStringContainsString('Текст первого поста', $first->contentText);
        $this->assertSame('https://forum.awd.ru/viewtopic.php?p=11860687#p11860687', $first->sourceUrl);

        $author = $first->author;
        $this->assertNotNull($author);
        $this->assertSame(394702, $author->id);
        $this->assertSame('@nnet.', $author->name);
        $this->assertSame('новичок', $author->rankName);
        $this->assertSame(19, $author->messagesCount);
        $this->assertSame('2015-12-11', $author->registeredOn);
        $this->assertSame('Ижевск', $author->city);
        $this->assertSame(2, $author->thanksGivenCount);
        $this->assertSame(0, $author->thanksReceivedCount);
        $this->assertSame(34, $author->age);
        $this->assertSame('Женский', $author->gender);
        $this->assertSame('https://forum.awd.ru/memberlist.php?mode=viewprofile&u=394702', $author->profileUrl);

        $second = $posts[1];
        $this->assertSame(11860699, $second->id);
        $this->assertSame(4496, $second->number);
        $this->assertSame(811438, $second->authorId);
        $this->assertNotNull($second->author);
        $this->assertSame('user2', $second->author->name);
        $this->assertSame('участник', $second->author->rankName);
        $this->assertSame(55, $second->author->messagesCount);
        $this->assertStringContainsString('images/avatars/upload/nophoto_811438.jpg', (string)$second->author->avatarUrl);
    }

    public function testParsesNextPageUrlFromPagination(): void
    {
        $parser = new ForumPostPageParser();
        $next = $parser->parseNextPageUrl('https://forum.awd.ru/viewtopic.php?t=415949&start=50', self::PAGE_HTML);
        $this->assertSame('https://forum.awd.ru/viewtopic.php?t=415949&start=100', $next);
    }

    public function testReturnsNullNextPageUrlOnLastPage(): void
    {
        $parser = new ForumPostPageParser();
        $next = $parser->parseNextPageUrl('https://forum.awd.ru/viewtopic.php?t=415949&start=150', self::PAGE_HTML);
        $this->assertNull($next);
    }

    public function testFirstPagePagination(): void
    {
        $parser = new ForumPostPageParser();
        $html = str_replace(
            'Страница <strong>2</strong> из <strong>4</strong>',
            'Страница <strong>1</strong> из <strong>4</strong>',
            self::PAGE_HTML,
        );
        $html = str_replace(
            '<span><strong>2</strong>',
            '<span><strong>1</strong><span class="page-sep">, </span><a href="./viewtopic.php?t=415949&amp;sid=abc&amp;start=50">2</a><span class="page-sep">, </span><a href="hidden-removed"',
            $html,
        );
        $next = $parser->parseNextPageUrl('https://forum.awd.ru/viewtopic.php?t=415949', $html);
        $this->assertSame('https://forum.awd.ru/viewtopic.php?t=415949&start=50', $next);
    }
}
