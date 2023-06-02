<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use Morpho\Base\Stack;
use Morpho\Testing\TestCase;

use function count;

class StackTest extends TestCase {
    public function testInterface() {
        $this->assertInstanceOf('\SplStack', new Stack());
    }

    public function testClear() {
        $stack = new Stack();
        $this->assertEquals(0, count($stack));

        $stack->push(1);
        $stack->push(2);

        $this->assertEquals(2, count($stack));

        $stack->clear();

        $this->assertEquals(0, count($stack));
    }

    public function testReplace() {
        $stack = new Stack();
        $stack->push('foo');
        $stack->replace('bar');
        $this->assertEquals(1, $stack->count());
        $this->assertEquals('bar', $stack[0]);
    }
}
