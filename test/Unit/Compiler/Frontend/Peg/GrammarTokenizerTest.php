<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\test\Unit\Compiler\Frontend\Peg;

use ArrayIterator;
use Morpho\Compiler\Frontend\Peg\IGrammarTokenizer;
use Morpho\Compiler\Frontend\Location;
use Morpho\Compiler\Frontend\Peg\GrammarTokenizer;
use Morpho\Compiler\Frontend\Peg\Token;
use Morpho\Compiler\Frontend\Peg\TokenType;
use Morpho\Testing\TestCase;

class GrammarTokenizerTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        $this->tokenizer = new GrammarTokenizer(new ArrayIterator([
            new Token(TokenType::NAME, 'foo', new Location(1, 0), new Location(1, 3), "line\n"),
        ]));
    }

    public function testInterface() {
        $this->assertInstanceOf(IGrammarTokenizer::class, $this->tokenizer);
    }

    public function testPeekToken() {
        $expectedTokens = [
            new Token(TokenType::NAME, 'foo', new Location(1, 0), new Location(1, 3), "line\n")
        ];
        $i = 0;
        $tokenGen = function () use ($expectedTokens, &$i) {
            yield $expectedTokens[$i++];
        };
        $tokenizer = new GrammarTokenizer($tokenGen());
        $this->assertSame(0, $tokenizer->index());
        $this->assertSame($expectedTokens[0], $tokenizer->peekToken());
        $this->assertSame(0, $tokenizer->index());
        $this->assertSame($expectedTokens[0], $tokenizer->peekToken());
        $this->assertSame(0, $tokenizer->index());
    }

    public function testIndex() {
        $this->assertSame(0, $this->tokenizer->index());
    }

    public function testNextToken() {
        $token = $this->tokenizer->nextToken();
        $this->assertInstanceOf(Token::class, $token);
    }
}