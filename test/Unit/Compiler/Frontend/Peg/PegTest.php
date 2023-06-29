<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\Peg\Grammar;
use Morpho\Compiler\Frontend\Peg\GrammarParser;
use Morpho\Compiler\Frontend\Peg\GrammarTokenizer;
use Morpho\Compiler\Frontend\Peg\Peg;
use Morpho\Compiler\ICompiler;
use Morpho\Testing\TestCase;

class PegTest extends TestCase {
    private Peg $parserGen;

    protected function setUp(): void {
        parent::setUp();
        $this->parserGen = new Peg();
    }

    public function testInterface(): void {
        $this->assertInstanceOf(ICompiler::class, $this->parserGen);
        $this->assertIsCallable($this->parserGen->frontend());
        $this->assertIsCallable($this->parserGen->midend());
        $this->assertIsCallable($this->parserGen->backend());
    }

    public function testParse() {
        [$grammar, $parser, $tokenizer] = Peg::parse('foo: bar');
        $this->assertInstanceOf(Grammar::class, $grammar);
        $this->assertInstanceOf(GrammarParser::class, $parser);
        $this->assertInstanceOf(GrammarTokenizer::class, $tokenizer);
    }

    public function testInvoke() {
        $this->markTestIncomplete();
    }
}