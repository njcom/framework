<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\IParserGen;
use Morpho\Compiler\Frontend\Peg\Grammar;
use Morpho\Compiler\Frontend\Peg\GrammarParser;
use Morpho\Compiler\Frontend\Peg\GrammarTokenizer;
use Morpho\Compiler\Frontend\Peg\Peg;
use Morpho\Testing\TestCase;

// https://github.com/python/cpython/blob/3.12/Lib/test/test_peg_generator/test_pegen.py
class PegTest extends TestCase {
    private Peg $peg;

    protected function setUp(): void {
        parent::setUp();
        $this->peg = new Peg();
    }

    public function testInterface() {
        $this->assertInstanceOf(IParserGen::class, $this->peg);
        $this->assertIsCallable($this->peg->frontend());
        $this->assertIsCallable($this->peg->midend());
        $this->assertIsCallable($this->peg->backend());
    }
    
    public function testBuild() {
        [$grammar, $parser, $tokenizer] = Peg::build('foo: bar');
        $this->assertInstanceOf(Grammar::class, $grammar);
        $this->assertInstanceOf(GrammarParser::class, $parser);
        $this->assertInstanceOf(GrammarTokenizer::class, $tokenizer);
    }

    public function testInvoke() {
        $this->markTestIncomplete();
        //$result = $this->peg->__invoke('foo: bar');
        //$this->assertNotEmpty()
    }
}