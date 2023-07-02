<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\Php;

use ArrayObject;
use Morpho\Tech\Php\ExceptionHandler;
use Morpho\Testing\TestCase;
use RuntimeException;

class ExceptionHandlerTest extends TestCase {
    public function testListeners() {
        $exceptionHandler = new ExceptionHandler();
        $listeners = $exceptionHandler->listeners();
        $this->assertEquals(new ArrayObject(), $listeners);
        $listeners->append(
            function () use (&$called) {
                $called = true;
            }
        );
        $fnListener = new class {
            public $called;

            public function __invoke(mixed $value): mixed {
                $this->called = true;
                return null;
            }
        };
        $listeners->append($fnListener);
        $exceptionHandler->handleException(new RuntimeException());
        $this->assertTrue($called);
        $this->assertTrue($fnListener->called);
    }
}