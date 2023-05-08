<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\IParser;
use Morpho\Compiler\Frontend\Peg\FirstSetCalculator;
use Morpho\Compiler\Frontend\Peg\Grammar;
use Morpho\Compiler\Frontend\Peg\GrammarParser;
use Morpho\Compiler\Frontend\Peg\GrammarTokenizer;
use Morpho\Compiler\Frontend\Peg\Parser;
use Morpho\Compiler\Frontend\Peg\Tokenizer;
use Morpho\Testing\TestCase;

/**
 * Based on https://github.com/python/cpython/blob/2b6f5c3483597abcb8422508aeffab04f500f568/Lib/test/test_peg_generator/test_first_sets.py#L8
 */
class GrammarParserTest extends TestCase {
    private GrammarParser $parser;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void {
        parent::setUp();
        $this->parser = new GrammarParser(
            new GrammarTokenizer(
                Tokenizer::tokenize($this->mkStream($this->getTestDirPath() . '/peg.peg'))
            )
        );
    }

    public function testInterface() {
        $this->assertInstanceOf(IParser::class, $this->parser);
        $this->assertInstanceOf(Parser::class, $this->parser);
    }

    // https://github.com/python/cpython/blob/2b6f5c3483597abcb8422508aeffab04f500f568/Lib/test/test_peg_generator/test_first_sets.py#L19
    // https://github.com/we-like-parsers/pegen/blob/main/tests/test_first_sets.py
    public function testAlternatives() {
        $grammarSource = file_get_contents($this->getTestDirPath() . '/000-alternative.gram');
        $this->assertSame(
            [
                "A" => ["'a'", "'-'"],
                "B" => ["'+'", "'b'"],
                "expr" => ["'+'", "'a'", "'b'", "'-'"],
                "start" => ["'+'", "'a'", "'b'", "'-'"],
            ],
            $this->calculateFirstSets($grammarSource)
        );
    }

    public function testInvoke() {
        $grammar = $this->parser->start();
        $this->assertInstanceOf(Grammar::class, $grammar);
    }

    private function parseString(string $s): Grammar {
        $tokenizer = new GrammarTokenizer(Tokenizer::tokenize($s));
        $parser = new GrammarParser($tokenizer);
        $grammar = $parser->start();
        if (!$grammar) {
            throw $parser->mkSyntaxError('Unable to parse grammar');
        }
        return $grammar;
    }

    private function calculateFirstSets(string $sourceGrammar): array {
        $grammar = $this->parseString($sourceGrammar);
        return (new FirstSetCalculator($grammar->rules()))->calculate();
    }
}
