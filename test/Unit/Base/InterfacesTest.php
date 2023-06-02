<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use Morpho\Base\IFn;
use Morpho\Testing\TestCase;

use function func_get_args;

class InterfacesTest extends TestCase {
    public function testIFn() {
        $obj = new class implements IFn {
            public $calledWith;

            public function __invoke(mixed $value): mixed {
                $this->calledWith = func_get_args();
                return null;
            }
        };
        $obj(['foo', 'bar', 'baz']);
        $this->assertEquals([['foo', 'bar', 'baz']], $obj->calledWith);
    }
}
