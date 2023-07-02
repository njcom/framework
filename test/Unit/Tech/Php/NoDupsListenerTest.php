<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\Php;

use Exception;
use Morpho\Tech\Php\NoDupsListener;
use Morpho\Testing\TestCase;

class NoDupsListenerTest extends TestCase {
    private string $lockFileDirPath;

    protected function setUp(): void {
        parent::setUp();
        $this->lockFileDirPath = $this->createTmpDir();
    }

    public function testInterface() {
        $this->assertIsCallable(new NoDupsListener(fn () => null, $this->lockFileDirPath));
    }

    public function testNoDupsOnException() {
        $numOfCalls = 0;
        $argsOfCalls = [];
        $listener = function (...$args) use (&$numOfCalls, &$argsOfCalls) {
            $numOfCalls++;
            $argsOfCalls[] = $args;
        };
        $ex = new Exception();
        //$listener->expects($this->once())->method('__invoke')->with($this->identicalTo($ex));
        $listener = new NoDupsListener($listener, $this->lockFileDirPath);
        $listener->__invoke($ex);
        $listener->__invoke($ex);
        $this->assertSame(1, $numOfCalls);
        $this->assertSame([[$ex]], $argsOfCalls);
    }
}