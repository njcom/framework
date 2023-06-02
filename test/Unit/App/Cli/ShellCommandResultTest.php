<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Cli;

use Countable;
use IteratorAggregate;
use Morpho\App\Cli\ICommandResult;
use Morpho\App\Cli\ShellCommandResult;
use Morpho\Testing\TestCase;

use function iterator_to_array;

class ShellCommandResultTest extends TestCase {
    public function testLines_DefaultArgs() {
        $res = new ShellCommandResult(
            'foo', 0, <<<OUT
 First line

        Second line

        Third line
        
OUT
            ,
            ''
        );

        $this->assertEquals(
            [
                'First line',
                'Second line',
                'Third line',
            ],
            iterator_to_array($res->lines())
        );
    }

    public function testInterface() {
        $result = new ShellCommandResult('foo', 0, '', '');
        $this->assertInstanceOf(IteratorAggregate::class, $result);
        $this->assertInstanceOf(ICommandResult::class, $result);
        $this->assertInstanceOf(Countable::class, $result);
    }
}
