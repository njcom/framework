<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\Peg\Grammar;
use Morpho\Compiler\Frontend\Peg\GrammarParser;
use Morpho\Compiler\Frontend\Peg\Tokenizer;
use Morpho\Compiler\Frontend\Peg\IRenderingActions;
use Morpho\Compiler\Frontend\Peg\Parser;
use Morpho\Compiler\Frontend\Peg\PythonTokenizer;
use Morpho\Testing\TestCase;

/**
 * Based on https://github.com/python/cpython/blob/3.12/Lib/test/test_peg_generator/test_pegen.py
 */
class GrammarParserTest extends TestCase {
    private GrammarParser $parser;

    protected function setUp(): void {
        parent::setUp();
        $this->parser = new GrammarParser(
            new Tokenizer(
                PythonTokenizer::tokenize($this->mkStream('foo: bar'))
            )
        );
    }

    public function testInterface() {
        $this->assertInstanceOf(Parser::class, $this->parser);
    }

    public function testInvoke() {
        $grammar = $this->parser->start();
        $this->assertInstanceOf(Grammar::class, $grammar);
        $rule = $grammar->rules['foo'];
        $this->assertInstanceOf(IRenderingActions::class, $rule);
    }
}
