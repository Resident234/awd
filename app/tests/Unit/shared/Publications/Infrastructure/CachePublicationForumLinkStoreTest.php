<?php

declare(strict_types=1);

namespace app\tests\Unit\shared\Publications\Infrastructure;

use app\shared\Publications\Dto\ForumPublicationRef;
use app\shared\Publications\Infrastructure\CachePublicationForumLinkStore;
use Codeception\Test\Unit;
use yii\caching\ArrayCache;

final class CachePublicationForumLinkStoreTest extends Unit
{
    private CachePublicationForumLinkStore $_store;

    protected function _before(): void
    {
        parent::_before();

        $this->_store = new CachePublicationForumLinkStore(new ArrayCache());
    }

    public function testRememberAndFind(): void
    {
        $this->_store->remember(5, new ForumPublicationRef('topic', 441040));

        $ref = $this->_store->find(5);

        $this->assertNotNull($ref);
        $this->assertSame('topic', $ref->type);
        $this->assertSame(441040, $ref->id);
        $this->assertTrue($ref->isTopic());
        $this->assertFalse($ref->isPost());
    }

    public function testFindReturnsNullForUnknownPublication(): void
    {
        $this->assertNull($this->_store->find(999));
    }

    public function testMoveTransfersTheLink(): void
    {
        $this->_store->remember(1, new ForumPublicationRef('post', 12297890));

        $this->_store->move(1, 7);

        $this->assertNull($this->_store->find(1));
        $ref = $this->_store->find(7);
        $this->assertNotNull($ref);
        $this->assertSame('post', $ref->type);
        $this->assertSame(12297890, $ref->id);
    }

    public function testMoveWithoutLinkDoesNothing(): void
    {
        $this->_store->move(1, 2);

        $this->assertNull($this->_store->find(2));
    }

    public function testMoveToSameIdKeepsTheLink(): void
    {
        $this->_store->remember(3, new ForumPublicationRef('topic', 10));

        $this->_store->move(3, 3);

        $this->assertNotNull($this->_store->find(3));
    }

    public function testForgetDropsTheLink(): void
    {
        $this->_store->remember(4, new ForumPublicationRef('topic', 12));
        $this->_store->forget(4);

        $this->assertNull($this->_store->find(4));
    }

    public function testForumRefHelpers(): void
    {
        $post = new ForumPublicationRef('post', 77);

        $this->assertTrue($post->isPost());
        $this->assertFalse($post->isTopic());
    }
}
