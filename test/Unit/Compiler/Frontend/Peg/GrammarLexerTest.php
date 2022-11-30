<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\ILexer;
use Morpho\Compiler\Frontend\Peg\GrammarLexer;
use Morpho\Compiler\Frontend\Peg\TokenInfo;
use Morpho\Testing\TestCase;

class GrammarLexerTest extends TestCase {
    private GrammarLexer $lexer;

    protected function setUp(): void {
        parent::setUp();
        $this->lexer = new GrammarLexer((function () {
            yield throw new \RuntimeException();
        })());
    }

    public function testInterface() {
        $this->assertInstanceOf(ILexer::class, $this->lexer);
    }

    public function testMark() {
        $this->assertSame(0, $this->lexer->mark());
    }

    public function testTokens_EmptyFile() {
        $grammarFile = $this->getTestDirPath() . '/empty.peg';
        $tokens = GrammarLexer::tokens($grammarFile);
        $this->assertInstanceOf(\Generator::class, $tokens);
        $tokens->rewind();
        $this->assertFalse($tokens->valid());
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertVoid($tokens->next());
        $this->assertNull($tokens->current());
    }

    public function testTokens() {
        $grammarFile = $this->getTestDirPath() . '/peg.peg';
        $tokens = GrammarLexer::tokens($grammarFile);
        $i = 0;
        $actual = '';
        foreach ($tokens as $token) {
            $this->assertInstanceOf(TokenInfo::class, $token);
            $actual .= (string) $token;
        }
        $expected = file_get_contents($this->getTestDirPath() . '/peg-tokens');
        $this->assertSame($expected, $actual);
        $this->assertGreaterThan(0, $i);
    }
}