<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\ILexer;
use Morpho\Compiler\Frontend\Peg\GrammarLexer;
use Morpho\Testing\TestCase;

class GrammarLexerTest extends TestCase {
    private GrammarLexer $lexer;

    protected function setUp(): void {
        parent::setUp();
        $this->markTestIncomplete();
        $this->lexer = new GrammarLexer(GrammarLexer::genTokens());
    }

    public function testInterface() {
        $this->assertInstanceOf(ILexer::class, $this->lexer);
    }

    public function testMark() {
        $this->assertSame(0, $this->lexer->mark());
    }

    public function testGenTokens() {
        $grammarFile = $this->getTestDirPath() . '/grammar.peg';


        d(GrammarLexer::genTokens());
    }
}