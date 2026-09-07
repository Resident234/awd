<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\shared\Forum\Contract\ForumHttpClientInterface;
use app\shared\Forum\Infrastructure\ForumPageNotFoundException;
use app\shared\Gallery\Contract\GalleryRepositoryInterface;
use app\shared\Gallery\Service\GalleryAlbumPageParser;
use app\shared\Gallery\Service\GalleryScanService;
use Codeception\Test\Unit;
use Psr\Log\NullLogger;

final class GalleryScanServiceTest extends Unit
{
    private const CONFIG = [
        'id' => '1',
        'code' => 'awd_gallery_albums',
        'base_url' => 'https://forum.awd.ru/gallery/album.php?album_id=',
        't_from' => '54902',
        't_to' => '54904',
        'is_active' => '1',
    ];

    private const ALBUM_HTML = <<<'HTML'
<html><body>
<ul class="linklist navlinks">
  <li>
    <a href="../index.php">Список форумов</a> <strong>&#8249;</strong>
    <a href="../gallery/index.php">Галерея</a> <strong>&#8249;</strong>
    <a href="../gallery/index.php?mode=personal">Личные альбомы</a> <strong>&#8249;</strong>
    <a href="../gallery/album.php?album_id=54902">8008</a> <strong>&#8249;</strong>
    <a href="../gallery/album.php?album_id=54903">Metallica</a>
  </li>
</ul>
<div id="page-body">
<h2><a href="../gallery/album.php?album_id=54903">Metallica</a></h2>
<div class="forumbg">
<span class="genmed"><a href="../gallery/images/upload/c0e/423/c0e423a6b89eb30aa97a20beb4369a48.jpg" title="Met 23-18" rel="&lt;a href='../gallery/image_page.php?album_id=54903&amp;image_id=2048754'&gt;Met 23-18&lt;/a&gt;" class="highslide"><img src="/thumb.jpg" alt="Met 23-18" /></a></span>
</div>
</div>
</body></html>
HTML;

    private GalleryRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $_repository;
    private ForumHttpClientInterface&\PHPUnit\Framework\MockObject\MockObject $_httpClient;

    protected function _before(): void
    {
        $this->_repository = $this->createMock(GalleryRepositoryInterface::class);
        $this->_httpClient = $this->createMock(ForumHttpClientInterface::class);
    }

    private function createService(): GalleryScanService
    {
        return new GalleryScanService(
            $this->_repository,
            $this->_httpClient,
            new GalleryAlbumPageParser(),
            new NullLogger(),
        );
    }

    public function testRunSavesAlbumsAndImagesAndSkipsFailures(): void
    {
        $this->_repository->method('activeConfig')->willReturn(self::CONFIG);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('save')->willReturn(
            ['album_inserted' => true, 'images_inserted' => 1],
            ['album_inserted' => false, 'images_inserted' => 0],
        );
        $this->_repository->expects($this->once())->method('markRun');
        $this->_repository->expects($this->once())->method('releaseLock');

        $this->_httpClient->method('get')->willReturnCallback(
            static function (string $url): string {
                return match (true) {
                    str_ends_with($url, '=54902'), str_ends_with($url, '=54904') => self::ALBUM_HTML,
                    str_ends_with($url, '=54903') => throw new ForumPageNotFoundException('Album does not exist.'),
                    default => throw new \RuntimeException('unexpected ' . $url),
                };
            },
        );

        $stats = $this->createService()->run(null, null, null);

        $this->assertSame(3, $stats['processed']);
        $this->assertSame(1, $stats['saved']);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame(1, $stats['not_found']);
        $this->assertSame(0, $stats['failed']);
        $this->assertSame(1, $stats['images_saved']);
        $this->assertSame(1, $stats['images_updated']);
    }

    public function testRunSavesLoginRequiredStub(): void
    {
        $config = array_merge(self::CONFIG, ['t_from' => '54910', 't_to' => '54910']);
        $this->_repository->method('activeConfig')->willReturn($config);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('save')->willReturn(['album_inserted' => true, 'images_inserted' => 0]);
        $this->_repository->expects($this->once())->method('save')->with(
            $this->callback(static function (\app\shared\Gallery\Dto\AlbumData $album): bool {
                return $album->id === 54910
                    && $album->sourceUrl === 'https://forum.awd.ru/gallery/album.php?album_id=54910'
                    && $album->loginRequired === true;
            }),
            [],
            $this->anything(),
        );
        $this->_httpClient->method('get')->willReturn(
            '<html><body>Для просмотра галереи вы должны быть авторизованы</body></html>',
        );

        $stats = $this->createService()->run(null, null, null);
        $this->assertSame(1, $stats['processed']);
        $this->assertSame(1, $stats['login_required']);
        $this->assertSame(1, $stats['saved']);
        $this->assertSame(0, $stats['failed']);
    }

    public function testRunWalksPagination(): void
    {
        $config = array_merge(self::CONFIG, ['t_from' => '54902', 't_to' => '54902']);
        $this->_repository->method('activeConfig')->willReturn($config);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('save')->willReturn(['album_inserted' => true, 'images_inserted' => 2]);

        $first = str_replace(
            '</body></html>',
            '<div class="pagination"><a href="../gallery/album.php?album_id=54902&amp;start=1">2</a></div></body></html>',
            self::ALBUM_HTML,
        );
        $second = self::ALBUM_HTML;

        $this->_httpClient->method('get')->willReturnCallback(
            static function (string $url) use ($first, $second): string {
                return match ($url) {
                    'https://forum.awd.ru/gallery/album.php?album_id=54902' => $first,
                    'https://forum.awd.ru/gallery/album.php?album_id=54902&start=1' => $second,
                    default => throw new \RuntimeException('unexpected ' . $url),
                };
            },
        );

        $stats = $this->createService()->run(null, null, null);
        $this->assertSame(1, $stats['processed']);
        $this->assertSame(1, $stats['saved']);
        $this->assertSame(2, $stats['images_saved']);
    }

    public function testRunRespectsLimit(): void
    {
        $this->_repository->method('activeConfig')->willReturn(self::CONFIG);
        $this->_repository->method('acquireLock')->willReturn(true);
        $this->_repository->method('save')->willReturn(['album_inserted' => true, 'images_inserted' => 0]);
        $this->_httpClient->method('get')->willReturn(self::ALBUM_HTML);

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
