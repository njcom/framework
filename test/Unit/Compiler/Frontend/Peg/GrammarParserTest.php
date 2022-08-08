<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\IParser;
use Morpho\Compiler\Frontend\Peg\Parser;
use Morpho\Compiler\Frontend\Peg\GrammarLexer;
use Morpho\Compiler\Frontend\Peg\GrammarParser;
use Morpho\Testing\TestCase;

class GrammarParserTest extends TestCase {
    private GrammarParser $parser;

    protected function setUp(): void {
        parent::setUp();
        $this->markTestIncomplete();
        $this->parser = new GrammarParser(new GrammarLexer(GrammarLexer::genTokens()));
    }

    public function testInterface() {
        $this->assertInstanceOf(IParser::class, $this->parser);
        $this->assertInstanceOf(Parser::class, $this->parser);
    }

    public function testInvoke() {
        $grammar = $this->parser->start();
        d($grammar);
    }
}