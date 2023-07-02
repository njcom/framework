<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use Countable;
use Iterator;
use Morpho\Base\Pipe;
use Morpho\Testing\TestCase;

class PipeTest extends TestCase {
    public function testInterface() {
        $pipe = new class extends Pipe {
            public function current(): callable {
            }

            public function count(): int {
                return 0;
            }
        };
        $this->assertIsCallable($pipe);
        $this->assertInstanceOf(Iterator::class, $pipe);
        $this->assertInstanceOf(Countable::class, $pipe);
    }

    public function testIterator() {
        $steps = [
            0 => fn () => null,
            1 => fn () => null,
        ];
        $pipe = new class ($steps) extends Pipe {
            private array $steps;

            public function __construct($steps) {
                $this->steps = $steps;
            }

            public function current(): callable {
                return $this->steps[$this->index];
            }

            public function valid(): bool {
                return isset($this->steps[$this->index]);
            }

            public function count(): int {
                return count($this->steps);
            }
        };
        $i = 0;
        foreach ($pipe as $key => $val) {
            $this->assertSame($steps[$key], $val);
            $i++;
        }
        $this->assertCount(2, $pipe);
        $this->assertSame(2, $pipe->count());
        $this->assertSame(2, $i);
    }
}
