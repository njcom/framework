<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Generator;
use Morpho\Compiler\Frontend\Peg\PythonTokenizerRe;
use Morpho\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

class PythonTokenizerReTest extends TestCase {
    public function testIsIdentifier() {
        $this->assertTrue(PythonTokenizerRe::isIdentifier('Grammar'));
        $this->assertFalse(PythonTokenizerRe::isIdentifier('0'));
        $this->assertFalse(PythonTokenizerRe::isIdentifier('123test'));
        $this->assertTrue(PythonTokenizerRe::isIdentifier('test123'));
        $this->assertTrue(PythonTokenizerRe::isIdentifier('_test123'));
        $this->assertTrue(PythonTokenizerRe::isIdentifier('_123'));
    }

    public function testEndPatterns_EndProgRe() {
        $re = PythonTokenizerRe::endPatterns()['"""'];
        $this->assertMatchesRegularExpression('~' . $re . '~', '"""' . "\n");
    }

    public function testTailEndOfSingleQuote() {
        $re = '~' . PythonTokenizerRe::TAIL_END_OF_SINGLE_QUOTE . '~s';
        preg_match($re, "pre1\\.\\.pre2'post", $match);
        $this->assertSame(["pre1\\.\\.pre2'"], $match);

        preg_match($re, "pre1\\.\\.pre2\"post", $match);
        $this->assertSame([], $match);
    }

    public function testTripleQuotedPrefixesAndSingleQuotedPrefixes() {
        $prefixes = PythonTokenizerRe::allStringPrefixes();
        $this->assertIsArray($prefixes);
        $n = count($prefixes);
        $this->assertTrue($n > 0);
        $this->assertCount($n * 2, PythonTokenizerRe::tripleQuotedPrefixes());
        $this->assertCount($n * 2, PythonTokenizerRe::singleQuotedPrefixes());
    }

    public function testGroupRe(): void {
        $this->assertSame('()', PythonTokenizerRe::groupRe());
        $this->assertSame('(a)', PythonTokenizerRe::groupRe('a'));
        $this->assertSame('(a|b)', PythonTokenizerRe::groupRe('a', 'b'));
    }

    public function testAnyRe(): void {
        $this->assertSame('(a)*', PythonTokenizerRe::anyRe('a'));
        $this->assertSame('(a|b)*', PythonTokenizerRe::anyRe('a', 'b'));
        try {
            PythonTokenizerRe::anyRe();
            $this->fail();
        } catch (UnexpectedValueException $e) {
            $this->assertSame("RE can't be empty", $e->getMessage());
        }
    }

    public function testMaybeRe(): void {
        $this->assertSame('(a)?', PythonTokenizerRe::maybeRe('a'));
        $this->assertSame('(a|b)?', PythonTokenizerRe::maybeRe('a', 'b'));
        try {
            PythonTokenizerRe::maybeRe();
            $this->fail();
        } catch (UnexpectedValueException $e) {
            $this->assertSame("RE can't be empty", $e->getMessage());
        }
    }

    public static function dataSimpleRes(): iterable {
        yield from self::genSamples([
            PythonTokenizerRe::HEX_NUMBER_RE => [
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
            PythonTokenizerRe::BIN_NUMBER_RE => [
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
            PythonTokenizerRe::OCT_NUMBER_RE => [
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
            PythonTokenizerRe::DEC_NUMBER_RE => [
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
            PythonTokenizerRe::WHITESPACE_RE => [
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
            PythonTokenizerRe::COMMENT_RE    => [
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
            PythonTokenizerRe::NAME_RE       => [
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

    #[DataProvider('dataSimpleRes')]
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

    #[DataProvider('dataIntNumberRe')]
    public function testIntNumberRe(string $input, bool $mustMatch) {
        $this->checkRe(PythonTokenizerRe::intNumberRe(), $input, $mustMatch);
    }

    public static function dataStringPrefixRe(): iterable {
        foreach (['', 'Br', 'rF', 'rb', 'r', 'F', 'fR', 'U', 'R', 'br', 'FR', 'B', 'Fr', 'f', 'b', 'u', 'rf', 'Rb', 'BR', 'RF', 'bR', 'RB', 'rB', 'fr', 'Rf'] as $prefix) {
            yield [$prefix, true];
        }
        foreach (['ab', '03'] as $prefix) {
            yield [$prefix, false];
        }
    }

    #[DataProvider('dataStringPrefixRe')]
    public function testStringPrefixRe(string $prefix, bool $mustMatch) {
        $this->checkRe(PythonTokenizerRe::stringPrefixRe(), $prefix, $mustMatch);
    }

    public function testContStrRe() {
        $re = PythonTokenizerRe::contStr();
        $this->checkRe($re, '""', true);
        $this->checkRe($re, '"123"', true);
        $this->checkRe($re, '"', false);
    }

    public function testFunnyRe() {
        $line = '"""' . "\n";
        $re = PythonTokenizerRe::funnyRe();
        preg_match($this->toFullRe($re), $line, $match, PREG_OFFSET_CAPTURE, 3);
        $this->assertSame(["\n", 3], $match[0]);
    }

    public function testPseudoExtrasRe() {
        $re = PythonTokenizerRe::pseudoExtrasRe();
        preg_match($this->toFullRe($re), '"""' . "\n", $match, PREG_OFFSET_CAPTURE, 3);
        $this->assertSame(["\n", 3], $match[0]);
    }

    public function testPseudoTokenRe(): void {
        $re = PythonTokenizerRe::pseudoTokenRe();

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
