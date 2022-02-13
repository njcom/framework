<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\Peg\Grammar;
use Morpho\Compiler\Frontend\Peg\Peg;
use Morpho\Compiler\ICompiler;
use Morpho\Testing\TestCase;

class PegTest extends TestCase {
    private Peg $peg;

    public function setUp(): void {
        parent::setUp();
        $this->peg = new Peg();
    }

    public function testInterface() {
        $this->assertInstanceOf(ICompiler::class, $this->peg);
    }

    public function testInvoke() {
        $context = ['grammar' => new Grammar()];
        $result = $this->peg->__invoke($context);
        $this->assertNotEmpty($result); // todo
    }
}