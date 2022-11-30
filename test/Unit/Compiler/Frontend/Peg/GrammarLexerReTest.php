<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\Peg\GrammarLexerRe;
use Morpho\Testing\TestCase;

class GrammarLexerReTest extends TestCase {
    public function testGroupRe(): void {
        $this->assertSame('()', GrammarLexerRe::groupRe());
        $this->assertSame('(a)', GrammarLexerRe::groupRe('a'));
        $this->assertSame('(a|b)', GrammarLexerRe::groupRe('a', 'b'));
    }

    public function testAnyRe(): void {
        $this->assertSame('(a)*', GrammarLexerRe::anyRe('a'));
        $this->assertSame('(a|b)*', GrammarLexerRe::anyRe('a', 'b'));
        try {
            GrammarLexerRe::anyRe();
            $this->fail();
        } catch (\UnexpectedValueException $e) {
            $this->assertSame("RE can't be empty", $e->getMessage());
        }
    }

    public function testMaybeRe(): void {
        $this->assertSame('(a)?', GrammarLexerRe::maybeRe('a'));
        $this->assertSame('(a|b)?', GrammarLexerRe::maybeRe('a', 'b'));
        try {
            GrammarLexerRe::maybeRe();
            $this->fail();
        } catch (\UnexpectedValueException $e) {
            $this->assertSame("RE can't be empty", $e->getMessage());
        }
    }

    public function testWhitespaceRe(): void {
        $this->assertMatchesRegularExpression($this->mkFullRe(GrammarLexerRe::whitespaceRe()), ' ');
        $this->assertDoesNotMatchRegularExpression($this->mkFullRe(GrammarLexerRe::whitespaceRe()), 'a');
    }

    private function mkFullRe(string $re): string {
        return '~' . $re . '~sD';
    }
}