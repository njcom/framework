<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\DataProcessing;

use Countable;
use Morpho\DataProcessing\Page;
use Morpho\DataProcessing\Pager;
use Morpho\Testing\TestCase;
use Traversable;

use function gettype;
use function range;

class PagerTest extends TestCase {
    private Pager $pager;

    protected function setUp(): void {
        parent::setUp();
        $this->pager = new Pager();
    }

    public function testInterface() {
        $this->assertInstanceOf(Traversable::class, $this->pager);
        $this->assertInstanceOf(Countable::class, $this->pager);
    }

    public function testDefaultPageSize() {
        $this->assertEquals(20, $this->pager->pageSize());
    }

    public function testPageSizeAccessors() {
        $this->pager->setPageSize(5);
        $this->assertEquals(5, $this->pager->pageSize());
    }

    public function testPagesCount() {
        $this->assertCount(0, $this->pager);
        $this->assertEquals(0, $this->pager->count());
        $this->assertEquals(0, $this->pager->totalPagesCount());
        $this->pager->setItems([1, 2, 3, 4, 5, 6, 7]);
        $this->pager->setPageSize(2);
        $this->assertCount(4, $this->pager);
        $this->assertEquals(4, $this->pager->count());
        $this->assertEquals(4, $this->pager->totalPagesCount());
        $this->pager->setPageSize(20);
        $this->assertCount(1, $this->pager);
        $this->assertEquals(1, $this->pager->count());
        $this->assertEquals(1, $this->pager->totalPagesCount());
        $this->assertTrue(gettype($this->pager->count()) == 'integer');
        $this->pager->setItems([]);
        $this->assertEquals(0, $this->pager->totalPagesCount());
        $pager = new Pager();
        $pager->setItems([]);
        $pager->setCurrentPageNumber(2);
        $this->assertEquals(0, $pager->totalPagesCount());
    }

    public function testMkPageByNumber() {
        $totalItemsCount = 7;
        $this->pager->setPageSize(2);
        $items = range(0, $totalItemsCount - 1);
        $this->pager->setItems($items);
        $this->assertEquals([6], $this->pager->mkPageByNumber(4)->toArr());
        $this->assertEquals([4, 5], $this->pager->mkPageByNumber(3)->toArr());
        $this->assertEquals([0, 1], $this->pager->mkPageByNumber(1)->toArr());
        // check bounds
        $this->assertEquals([], $this->pager->mkPageByNumber(5)->toArr());
        $this->assertEquals([0, 1], $this->pager->mkPageByNumber(-1)->toArr());
    }

    public function testIterator() {
        $items = [1, 2, 3, 4, 5, 6, 7];
        $this->pager->setItems($items);
        $this->pager->setPageSize(2);
        $this->assertNull($this->pager->rewind());
        $this->assertTrue($this->pager->valid());
        $this->assertEquals(1, $this->pager->key());
        $this->assertInstanceOf(Page::class, $this->pager->current());
        $this->assertEquals([1, 2], $this->pager->current()->toArr());
        $this->assertNull($this->pager->next());
        $this->assertTrue($this->pager->valid());
        $this->assertEquals(2, $this->pager->key());
        $this->assertEquals([3, 4], $this->pager->current()->toArr());
        $this->assertNull($this->pager->next());
        $this->assertTrue($this->pager->valid());
        $this->assertEquals(3, $this->pager->key());
        $this->assertEquals([5, 6], $this->pager->current()->toArr());
        $this->assertNull($this->pager->next());
        $this->assertTrue($this->pager->valid());
        $this->assertEquals(4, $this->pager->key());
        $this->assertEquals([7], $this->pager->current()->toArr());
        $this->assertNull($this->pager->next());
        $this->assertFalse($this->pager->valid());
        $this->assertEquals(4, $this->pager->key());
        $this->assertEquals([7], $this->pager->current()->toArr());
    }
}