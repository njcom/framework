<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\ITokenizer;
use Morpho\Compiler\Frontend\Location;
use Morpho\Compiler\Frontend\Peg\CachingGrammarTokenizer;
use Morpho\Compiler\Frontend\Peg\TokenInfo;
use Morpho\Compiler\Frontend\Peg\TokenType;
use Morpho\Testing\TestCase;

class CachingGrammarTokenizerTest extends TestCase {
    public function testInterface() {
        $this->assertInstanceOf(ITokenizer::class, new CachingGrammarTokenizer((function () { yield 123; })()));

    }
    public function testPeek() {
        $expectedTokens = [
            new TokenInfo(TokenType::NAME, 'foo', new Location(1, 0), new Location(1, 3), "line\n")
        ];
        $i = 0;
        $tokenGen = function () use ($expectedTokens, &$i) {
            yield $expectedTokens[$i++];
        };
        $tokenizer = new CachingGrammarTokenizer($tokenGen());
        $this->assertSame(0, $tokenizer->index());
        $this->assertSame($expectedTokens[0], $tokenizer->peek());
        $this->assertSame(0, $tokenizer->index());
        $this->assertSame($expectedTokens[0], $tokenizer->peek());
        $this->assertSame(0, $tokenizer->index());
    }
}