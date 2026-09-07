<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\shared\Forum\Infrastructure\ForumPageNotFoundException;
use app\shared\Gallery\Service\GalleryAlbumPageParser;
use Codeception\Test\Unit;
use RuntimeException;

final class GalleryAlbumPageParserTest extends Unit
{
    private const ALBUM_URL = 'https://forum.awd.ru/gallery/album.php?album_id=54903';

    private const ALBUM_HTML = <<<'HTML'
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru">
<body id="phpbb" class="section-album ltr">
<div id="wrap">
<div id="page-header">
	<div class="navbar">
		<div class="inner"><span class="corners-top"><span></span></span>
		<ul class="linklist navlinks">
  <li>
    <a href="../index.php" accesskey="h">Список форумов</a>
     <strong>&#8249;</strong>
    <a href="../gallery/index.php">Галерея</a>
     <strong>&#8249;</strong>
    <a href="../gallery/index.php?mode=personal">Личные альбомы</a>
     <strong>&#8249;</strong>
    <a href="../gallery/album.php?album_id=54902">8008</a>
     <strong>&#8249;</strong>
    <a href="../gallery/album.php?album_id=54903">Metallica</a>
  </li>
		</ul>
		<span class="corners-bottom"><span></span></span></div>
	</div>
</div>
<a name="start_here"></a>
<div id="page-body">
<h2><a href="../gallery/album.php?album_id=54903&amp;sid=x">Metallica</a></h2>
<div class="topic-actions">
	<div class="pagination">
		4 фото
	</div>
</div>
<div class="forumbg">
	<ul class="topiclist topics">
		<li class="bg2">
			<table class="forumline"><tr>
				<td class="bg2" valign="top">
					<table class="forumline">
						<tr><td align="center" class="bg1">
							<span class="genmed"><a href="../gallery/images/upload/c0e/423/c0e423a6b89eb30aa97a20beb4369a48.jpg" title="Met 23-18" rel="&lt;a href='../gallery/image_page.php?album_id=54903&amp;image_id=2048754'&gt;Met 23-18&lt;/a&gt;" class="highslide" onclick="return hs.expand(this, { captionEval: 'this.a.rel'})"><img src="/fake_non_existing_directory/2048754/thumb/c0e/423/c0e423a6b89eb30aa97a20beb4369a48.jpg" alt="Met 23-18" title="Met 23-18" /></a></span>
						</td></tr>
						<tr><td class="bg2">
							<div class="gensmall"><!--Название: --><a href="../gallery/image_page.php?album_id=54903&amp;image_id=2048754&amp;sk=t&amp;sd=d" title="Met 23-18"><span style="font-weight: bold;">Met 23-18</span></a><br /></div>
						</td></tr>
					</table>
				</td>
				<td class="bg2" valign="top">
					<table class="forumline">
						<tr><td align="center" class="bg1">
							<span class="genmed"><a href="../gallery/images/upload/1da/eb3/1daeb3dbba5584a5fa6b0735e0d4996d.jpg" title="met20-20" rel="&lt;a href='../gallery/image_page.php?album_id=54903&amp;image_id=2048753'&gt;met20-20&lt;/a&gt;" class="highslide"><img src="/fake_non_existing_directory/2048753/thumb/1da/eb3.jpg" alt="met20-20" title="met20-20" /></a></span>
						</td></tr>
					</table>
				</td>
			</tr></table>
		</li>
	</ul>
</div>
</div>
</div>
</body>
</html>
HTML;

    public function testParsesAlbumWithTitleUsernameAndImages(): void
    {
        $parser = new GalleryAlbumPageParser();
        $result = $parser->parse(54903, self::ALBUM_URL, self::ALBUM_HTML);

        $album = $result['album'];
        $this->assertSame(54903, $album->id);
        $this->assertSame(self::ALBUM_URL, $album->sourceUrl);
        $this->assertSame('Metallica', $album->title);
        $this->assertSame('8008', $album->username);
        $this->assertFalse($album->loginRequired);

        $images = $result['images'];
        $this->assertCount(2, $images);

        $this->assertSame(2048754, $images[0]->id);
        $this->assertSame(54903, $images[0]->albumId);
        $this->assertSame('Met 23-18', $images[0]->title);
        $this->assertSame(
            'https://forum.awd.ru/gallery/images/upload/c0e/423/c0e423a6b89eb30aa97a20beb4369a48.jpg',
            $images[0]->imageUrl,
        );
        $this->assertSame(
            'https://forum.awd.ru/gallery/image_page.php?album_id=54903&image_id=2048754',
            $images[0]->sourceUrl,
        );

        $this->assertSame(2048753, $images[1]->id);
        $this->assertSame('met20-20', $images[1]->title);
    }

    public function testParsesCommonAlbumWithoutUsername(): void
    {
        $parser = new GalleryAlbumPageParser();
        $html = str_replace(
            '<a href="../gallery/index.php?mode=personal">Личные альбомы</a>',
            '<a href="../gallery/index.php">Разные категории</a>',
            self::ALBUM_HTML,
        );
        $result = $parser->parse(26701, 'https://forum.awd.ru/gallery/album.php?album_id=26701', $html);
        $this->assertNull($result['album']->username);
    }

    public function testThrowsOnMissingAlbum(): void
    {
        $parser = new GalleryAlbumPageParser();
        $this->expectException(ForumPageNotFoundException::class);
        $parser->parse(
            499999999,
            'https://forum.awd.ru/gallery/album.php?album_id=499999999',
            '<html><body><p>Запрошенный альбом не существует.</p></body></html>',
        );
    }

    public function testThrowsOnInvalidHtml(): void
    {
        $parser = new GalleryAlbumPageParser();
        $this->expectException(RuntimeException::class);
        $parser->parse(1, 'https://forum.awd.ru/gallery/album.php?album_id=1', '<html><body><p>No album here</p></body></html>');
    }

    public function testParsesNextPageUrl(): void
    {
        $parser = new GalleryAlbumPageParser();
        $html = <<<'HTML'
<html><body>
<div class="pagination">
	1563 фото &bull; Страница <strong>1</strong> из <strong>16</strong> &bull;
	<span><strong>1</strong>, <a href="../gallery/album.php?album_id=11186&amp;sk=t&amp;sd=d&amp;st=0&amp;sid=x&amp;start=100">2</a>, <a href="../gallery/album.php?album_id=11186&amp;sk=t&amp;sd=d&amp;st=0&amp;sid=x&amp;start=200">3</a></span>
</div>
</body></html>
HTML;
        $next = $parser->parseNextPageUrl('https://forum.awd.ru/gallery/album.php?album_id=11186', $html);
        $this->assertSame('https://forum.awd.ru/gallery/album.php?album_id=11186&start=100', $next);
    }

    public function testReturnsNullNextPageUrlOnLastPage(): void
    {
        $parser = new GalleryAlbumPageParser();
        $html = <<<'HTML'
<html><body>
<div class="pagination">
	4 фото
</div>
<div class="pagination"><a href="../gallery/album.php?album_id=54903&amp;mode=slide_show">Слайдшоу</a> &bull;&nbsp;</div>
</body></html>
HTML;
        $next = $parser->parseNextPageUrl('https://forum.awd.ru/gallery/album.php?album_id=54903', $html);
        $this->assertNull($next);
    }

    public function testSkipsLowerStartLinksOnSecondPage(): void
    {
        $parser = new GalleryAlbumPageParser();
        $html = <<<'HTML'
<html><body>
<div class="pagination">
	<span><a href="../gallery/album.php?album_id=11186&amp;start=0">1</a>, <strong>2</strong>, <a href="../gallery/album.php?album_id=11186&amp;start=200">3</a></span>
</div>
</body></html>
HTML;
        $next = $parser->parseNextPageUrl(
            'https://forum.awd.ru/gallery/album.php?album_id=11186&start=100',
            $html,
        );
        $this->assertSame('https://forum.awd.ru/gallery/album.php?album_id=11186&start=200', $next);
    }
}
