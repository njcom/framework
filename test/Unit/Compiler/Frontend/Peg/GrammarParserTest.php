<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\IParser;
use Morpho\Compiler\Frontend\Peg\Grammar;
use Morpho\Compiler\Frontend\Peg\GrammarParser;
use Morpho\Compiler\Frontend\Peg\GrammarTokenizer;
use Morpho\Compiler\Frontend\Peg\Parser;
use Morpho\Compiler\Frontend\Peg\Tokenizer;
use Morpho\Testing\TestCase;

class GrammarParserTest extends TestCase {
    private GrammarParser $parser;

    protected function setUp(): void {
        parent::setUp();
        $this->parser = new GrammarParser(
            new GrammarTokenizer(
                Tokenizer::tokenize($this->mkStream('foo: bar'))
            )
        );
    }

    public function testInterface() {
        $this->assertInstanceOf(IParser::class, $this->parser);
        $this->assertInstanceOf(Parser::class, $this->parser);
    }

    public function testInvoke() {
        $grammar = $this->parser->start();
        $this->assertInstanceOf(Grammar::class, $grammar);
    }
}
