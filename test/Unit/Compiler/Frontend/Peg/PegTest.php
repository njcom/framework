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

    public function testParse() {
        [$grammar, $parser, $tokenizer] = Peg::parse('foo: bar');
        $this->assertInstanceOf(Grammar::class, $grammar);
        $this->assertInstanceOf(GrammarParser::class, $parser);
        $this->assertInstanceOf(GrammarTokenizer::class, $tokenizer);
    }

    public function testInvoke() {
        // @todo: write parser for the TOML (https://toml.io/en/) for testing it as the whole, [ABNF grammar](https://github.com/toml-lang/toml/blob/1.0.0/toml.abnf)
        // @todo: ABNF grammar parser and converter to PEG https://www.ietf.org/rfc/rfc5234.txt
        $grammarSource = <<<OUT
        start[return_type]:
            | first_alt
            | second_alt
        first_alt: '123'
        second_alt: '456'
        OUT;
        d($this->parserGen->__invoke($grammarSource));
    }
}
