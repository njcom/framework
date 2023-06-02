<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Generator;
use Morpho\Compiler\Frontend\Peg\TokenizerRe;
use Morpho\Testing\TestCase;
use UnexpectedValueException;

class TokenizerReTest extends TestCase {
    public function testIsIdentifier() {
        $this->assertTrue(TokenizerRe::isIdentifier('Grammar'));
        $this->assertFalse(TokenizerRe::isIdentifier('0'));
        $this->assertFalse(TokenizerRe::isIdentifier('123test'));
        $this->assertTrue(TokenizerRe::isIdentifier('test123'));
        $this->assertTrue(TokenizerRe::isIdentifier('_test123'));
        $this->assertTrue(TokenizerRe::isIdentifier('_123'));
    }

    public function testEndPatterns_EndProgRe() {
        $re = TokenizerRe::endPatterns()['"""'];
        $this->assertMatchesRegularExpression('~' . $re . '~', '"""' . "\n");
    }

    public function testTailEndOfSingleQuote() {
        $re = '~' . TokenizerRe::TAIL_END_OF_SINGLE_QUOTE . '~s';
        preg_match($re, "pre1\\.\\.pre2'post", $match);
        $this->assertSame(["pre1\\.\\.pre2'"], $match);

        preg_match($re, "pre1\\.\\.pre2\"post", $match);
        $this->assertSame([], $match);
    }

    public function testTripleQuotedPrefixesAndSingleQuotedPrefixes() {
        $prefixes = TokenizerRe::allStringPrefixes();
        $this->assertIsArray($prefixes);
        $n = count($prefixes);
        $this->assertTrue($n > 0);
        $this->assertCount($n * 2, TokenizerRe::tripleQuotedPrefixes());
        $this->assertCount($n * 2, TokenizerRe::singleQuotedPrefixes());
    }

    public function testGroupRe(): void {
        $this->assertSame('()', TokenizerRe::groupRe());
        $this->assertSame('(a)', TokenizerRe::groupRe('a'));
        $this->assertSame('(a|b)', TokenizerRe::groupRe('a', 'b'));
    }

    public function testAnyRe(): void {
        $this->assertSame('(a)*', TokenizerRe::anyRe('a'));
        $this->assertSame('(a|b)*', TokenizerRe::anyRe('a', 'b'));
        try {
            TokenizerRe::anyRe();
            $this->fail();
        } catch (UnexpectedValueException $e) {
            $this->assertSame("RE can't be empty", $e->getMessage());
        }
    }

    public function testMaybeRe(): void {
        $this->assertSame('(a)?', TokenizerRe::maybeRe('a'));
        $this->assertSame('(a|b)?', TokenizerRe::maybeRe('a', 'b'));
        try {
            TokenizerRe::maybeRe();
            $this->fail();
        } catch (UnexpectedValueException $e) {
            $this->assertSame("RE can't be empty", $e->getMessage());
        }
    }

    public static function dataSimpleRes(): iterable {
        yield from self::genSamples([
            TokenizerRe::HEX_NUMBER_RE => [
                [
                    '0x_0f',
                    true,
                ],
                [
                    '0x0f',
                    true,
                ],
                [
                    '0x1b',
                    true,
                ],
                [
                    '0X1B',
                    true,
                ],
                [
                    '0x1g',
                    false,
                ],
                [
                    '123',
                    false,
                ],
                [
                    '0',
                    false,
                ],
                [
                    '0x00',
                    true,
                ],
                [
                    '0x00_af',
                    true,
                ],
                [
                    '0xf',
                    true,
                ],
            ],
            TokenizerRe::BIN_NUMBER_RE => [
                [
                    '0b01_10',
                    true,
                ],
                [
                    '0b03',
                    false,
                ],
                [
                    '1',
                    false,
                ],
                [
                    '0',
                    false,
                ],
                [
                    '0b1',
                    true,
                ],
                [
                    '0b0',
                    true,
                ],
                [
                    '0b0001110_1101',
                    true,
                ],
                [
                    '0b0001110_1201',
                    false,
                ],
            ],
            TokenizerRe::OCT_NUMBER_RE => [
                [
                    '0o0703331',
                    true,
                ],
                [
                    '0O0703331',
                    true,
                ],
                [
                    '0O07033_31',
                    true,
                ],
                [
                    '0O08033_31',
                    false,
                ],
                [
                    '0',
                    false,
                ],
                [
                    '3',
                    false,
                ],
            ],
            TokenizerRe::DEC_NUMBER_RE => [
                [
                    '0',
                    true,
                ],
                [
                    '00',
                    true,
                ],
                [
                    '1331000',
                    true,
                ],
                [
                    '01',
                    false,
                ],
                [
                    '08',
                    false,
                ],
                [
                    '9_323011870',
                    true,
                ],
                [
                    '0xaf',
                    false,
                ],
            ],
            TokenizerRe::WHITESPACE_RE => [
                [
                    '',
                    true,
                ],
                [
                    ' ',
                    true,
                ],
                [
                    'a',
                    false,
                ],
            ],
            TokenizerRe::COMMENT_RE    => [
                [
                    '#',
                    true,
                ],
                [
                    '# abc',
                    true,
                ],
                [
                    'abc',
                    false,
                ],
                [
                    '',
                    false,
                ],
            ],
            TokenizerRe::NAME_RE       => [
                [
                    'abc',
                    true,
                ],
                [
                    '123',
                    true,
                ],
                [
                    '',
                    false,
                ],
            ],
        ]);
    }

    /**
     * @dataProvider dataSimpleRes
     * @param string $re
     * @param string $input
     * @param bool   $mustMatch
     * @return void
     */
    public function testSimpleRes(string $re, string $input, bool $mustMatch): void {
        $this->checkRe($re, $input, $mustMatch);
    }

    public static function dataIntNumberRe(): iterable {
        return [
            [
                '0x_0f',
                true,
            ],
            [
                '0x0f',
                true,
            ],
            [
                '0x1b',
                true,
            ],
            [
                '0X1B',
                true,
            ],
            [
                '0x1g',
                false,
            ],
            [
                '123',
                true,
            ],
            [
                '0',
                true,
            ],
            [
                '0x00',
                true,
            ],
            [
                '0x00_af',
                true,
            ],
            [
                '0xf',
                true,
            ],
            [
                '0b01_10',
                true,
            ],
            [
                '0b03',
                false,
            ],
            [
                '1',
                true,
            ],
            [
                '0',
                true,
            ],
            [
                '0b1',
                true,
            ],
            [
                '0b0',
                true,
            ],
            [
                '0b0001110_1101',
                true,
            ],
            [
                '0b0001110_1201',
                false,
            ],
            [
                '0o0703331',
                true,
            ],
            [
                '0O0703331',
                true,
            ],
            [
                '0O07033_31',
                true,
            ],
            [
                '0O08033_31',
                false,
            ],
            [
                '0',
                true,
            ],
            [
                '3',
                true,
            ],
            [
                '0',
                true,
            ],
            [
                '00',
                true,
            ],
            [
                '1331000',
                true,
            ],
            [
                '01',
                false,
            ],
            [
                '08',
                false,
            ],
            [
                '9_323011870',
                true,
            ],
            [
                '0xaf',
                true,
            ],
        ];
    }

    /**
     * @dataProvider dataIntNumberRe
     */
    public function testIntNumberRe(string $input, bool $mustMatch) {
        $this->checkRe(TokenizerRe::intNumberRe(), $input, $mustMatch);
    }

    public static function dataStringPrefixRe(): iterable {
        foreach (['', 'Br', 'rF', 'rb', 'r', 'F', 'fR', 'U', 'R', 'br', 'FR', 'B', 'Fr', 'f', 'b', 'u', 'rf', 'Rb', 'BR', 'RF', 'bR', 'RB', 'rB', 'fr', 'Rf'] as $prefix) {
            yield [$prefix, true];
        }
        foreach (['ab', '03'] as $prefix) {
            yield [$prefix, false];
        }
    }

    /**
     * @dataProvider dataStringPrefixRe
     */
    public function testStringPrefixRe(string $prefix, bool $mustMatch) {
        $this->checkRe(TokenizerRe::stringPrefixRe(), $prefix, $mustMatch);
    }

    public function testContStrRe() {
        $re = TokenizerRe::contStr();
        $this->checkRe($re, '""', true);
        $this->checkRe($re, '"123"', true);
        $this->checkRe($re, '"', false);
    }

    public function testFunnyRe() {
        $line = '"""' . "\n";
        $re = TokenizerRe::funnyRe();
        preg_match($this->toFullRe($re), $line, $match, PREG_OFFSET_CAPTURE, 3);
        $this->assertSame(["\n", 3], $match[0]);
    }

    public function testPseudoExtrasRe() {
        $re = TokenizerRe::pseudoExtrasRe();
        preg_match($this->toFullRe($re), '"""' . "\n", $match, PREG_OFFSET_CAPTURE, 3);
        $this->assertSame(["\n", 3], $match[0]);
    }

    public function testPseudoTokenRe(): void {
        $re = TokenizerRe::pseudoTokenRe();

        $this->assertMatchesRegularExpression($this->toLineRe($re), 'abc');

        $line = '"""' . "\n";
        preg_match($this->toFullRe($re), $line, $match, PREG_OFFSET_CAPTURE, 3);
        $this->assertSame("\n", $match[1][0]);
    }

    private function toLineRe(string $re): string {
        return self::toFullRe('^' . $re . '$');
    }

    private function toFullRe(string $re): string {
        return '~' . $re . '~sADu';
    }

    private function checkRe(string $re, string $input, bool $mustMatch): void {
        $re = self::toLineRe($re);
        if ($mustMatch) {
            $this->assertMatchesRegularExpression($re, $input);
        } else {
            $this->assertDoesNotMatchRegularExpression($re, $input);
        }
    }

    private static function genSamples(array $samples): Generator {
        foreach ($samples as $re => $pairs) {
            foreach ($pairs as $pair) {
                yield [
                    $re,
                    $pair[0],
                    $pair[1],
                ];
            }
        }
    }
}