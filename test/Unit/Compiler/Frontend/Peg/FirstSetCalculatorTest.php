<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\Peg\FirstSetCalculator;
use Morpho\Compiler\Frontend\Peg\Grammar;
use Morpho\Compiler\Frontend\Peg\GrammarParser;
use Morpho\Compiler\Frontend\Peg\GrammarTokenizer;
use Morpho\Compiler\Frontend\Peg\GrammarVisitor;
use Morpho\Compiler\Frontend\Peg\Tokenizer;
use Morpho\Testing\TestCase;

/**
 * Based on https://github.com/python/cpython/blob/main/Lib/test/test_peg_generator/test_first_sets.py
 */
class FirstSetCalculatorTest extends TestCase {
    public function testInterface() {
        $this->assertInstanceOf(GrammarVisitor::class, new FirstSetCalculator([]));
    }

    public function testAlternatives(): void {
        $grammar = file_get_contents($this->getTestDirPath() . '/000-alternative.gram');
        $this->checkCalculator(
            [
                "A"     => ["'a'", "'-'"],
                "B"     => ["'+'", "'b'"],
                "expr"  => ["'+'", "'a'", "'b'", "'-'"],
                "start" => ["'+'", "'a'", "'b'", "'-'"],
            ],
            $grammar
        );
    }

    public function testOptionals(): void {
        $grammar = <<<OUT
        start: expr NEWLINE
        expr: ['a'] ['b'] 'c'
        OUT;
        $this->checkCalculator(
            [
                'expr'  => ["'c'", "'a'", "'b'"],
                'start' => ["'c'", "'a'", "'b'"],
            ],
            $grammar
        );
    }

    public function testRepeatWithSeparator(): void {
        $grammar = <<<OUT
        start: ','.thing+ NEWLINE
        thing: NUMBER
        OUT;
        $this->checkCalculator(
            [
                'thing' => ['NUMBER'],
                'start' => ['NUMBER'],
            ],
            $grammar
        );
    }

    public function testOptionalOperator(): void {
        $grammar = <<<OUT
        start: sum NEWLINE
        sum: (term)? 'b'
        term: NUMBER
        OUT;
        $this->checkCalculator(
            [
                "term"  => ["NUMBER"],
                "sum"   => ["NUMBER", "'b'"],
                "start" => ["'b'", "NUMBER"],
            ],
            $grammar,
        );
    }

    public function testOptionalLiteral(): void {
        $grammar = <<<OUT
        start: sum NEWLINE
        sum: '+' ? term
        term: NUMBER
        OUT;
        $this->checkCalculator(
            [
                'term'  => ['NUMBER'],
                'sum'   => ["'+'", "NUMBER"],
                "start" => ["'+'", "NUMBER"],
            ],
            $grammar
        );
    }

    public function testOptionalBefore(): void {
        $grammar = <<<OUT
        start: term NEWLINE
        term: ['+'] NUMBER
        OUT;
        $this->checkCalculator(
            [
                "term"  => ["NUMBER", "'+'"],
                "start" => ["NUMBER", "'+'"],
            ],
            $grammar,
        );
    }

    public function testRepeat0(): void {
        $grammar = <<<OUT
        start: thing* "+" NEWLINE
        thing: NUMBER
        OUT;
        $this->checkCalculator(
            [
                "thing" => ["NUMBER"],
                "start" => ['"+"', "NUMBER"],
            ],
            $grammar,
        );
    }

    public function testRepeat0WithGroup(): void {
        $grammar = <<<OUT
        start: ('+' '-')* term NEWLINE
        term: NUMBER
        OUT;
        $this->checkCalculator(
            [
                "term"  => ["NUMBER"],
                "start" => ["'+'", "NUMBER"],
            ],
            $grammar,
        );
    }

    public function testRepeat1(): void {
        $grammar = <<<OUT
        start: thing+ '-' NEWLINE
        thing: NUMBER
        OUT;
        $this->checkCalculator(
            [
                "thing" => ["NUMBER"],
                "start" => ["NUMBER"],
            ],
            $grammar,
        );
    }

    public function testRepeat1WithGroup(): void {
        $grammar = <<<OUT
        start: ('+' term)+ term NEWLINE
        term: NUMBER
        OUT;
        $this->checkCalculator(
            [
                "term"  => ["NUMBER"],
                "start" => ["'+'"],
            ],
            $grammar,
        );
    }

    public function testGather(): void {
        $grammar = <<<OUT
        start: ','.thing+ NEWLINE
        thing: NUMBER
        OUT;
        $this->checkCalculator(
            [
                "thing" => ["NUMBER"],
                "start" => ["NUMBER"],
            ],
            $grammar,
        );
    }

    public function testPositiveLookahead(): void {
        $grammar = <<<OUT
        start: expr NEWLINE
        expr: &'a' opt
        opt: 'a' | 'b' | 'c'
        OUT;
        $this->checkCalculator(
            [
                "expr"  => ["'a'"],
                "start" => ["'a'"],
                "opt"   => ["'b'", "'c'", "'a'"],
            ],
            $grammar,
        );
    }

    public function testNegativeLookahead(): void {
        $grammar = <<<OUT
        start: expr NEWLINE
        expr: !'a' opt
        opt: 'a' | 'b' | 'c'
        OUT;
        $this->checkCalculator(
            [
                "opt"   => ["'b'", "'a'", "'c'"],
                "expr"  => ["'b'", "'c'"],
                "start" => ["'b'", "'c'"],
            ],
            $grammar,
        );
    }

    public function testLeftRecursion(): void {
        $grammar = <<<OUT
        start: expr NEWLINE
        expr: ('-' term | expr '+' term | term)
        term: NUMBER
        foo: 'foo'
        bar: 'bar'
        baz: 'baz'
        OUT;
        $this->checkCalculator(
            [
                "expr"  => ["NUMBER", "'-'"],
                "term"  => ["NUMBER"],
                "start" => ["NUMBER", "'-'"],
                "foo"   => ["'foo'"],
                "bar"   => ["'bar'"],
                "baz"   => ["'baz'"],
            ],
            $grammar,
        );
    }

    public function testAdvanceLeftRecursion(): void {
        $grammar = <<<OUT
        start: NUMBER | sign start
        sign: ['-']
        OUT;
        $this->checkCalculator(
            [
                "sign"  => ["'-'", ""],
                "start" => ["'-'", "NUMBER"],
            ],
            $grammar,
        );
    }

    public function testMutualLeftRecursion(): void {
        $grammar = <<<OUT
        start: foo 'E'
        foo: bar 'A' | 'B'
        bar: foo 'C' | 'D'
        OUT;
        $this->checkCalculator(
            [
                "foo"   => ["'D'", "'B'"],
                "bar"   => ["'D'"],
                "start" => ["'D'", "'B'"],
            ],
            $grammar,
        );
    }

    public function testNastyLeftRecursion(): void {
        # TODO: Validate this
        $grammar = <<<OUT
        start: target '='
        target: maybe '+' | NAME
        maybe: maybe '-' | target
        OUT;
        $this->checkCalculator(
            [
                "maybe"  => [],
                "target" => ["NAME"],
                "start"  => ["NAME"],
            ],
            $grammar,
        );
    }

    public function testNullableRule(): void {
        $grammar = <<<OUT
        start: sign thing $
        sign: ['-']
        thing: NUMBER
        OUT;
        $this->checkCalculator(
            [
                "sign"  => ["", "'-'"],
                "thing" => ["NUMBER"],
                "start" => ["NUMBER", "'-'"],
            ],
            $grammar,
        );
    }

    public function testEpsilonProductionInStartRule(): void {
        $grammar = <<<OUT
        start: ['-'] $
        OUT;
        $this->checkCalculator(
            [
                "start" => ["ENDMARKER", "'-'"],
            ],
            $grammar,
        );
    }

    public function testMultipleNullableRules(): void {
        $grammar = <<<OUT
        start: sign thing other another $
        sign: ['-']
        thing: ['+']
        other: '*'
        another: '/'
        OUT;
        $this->checkCalculator(
            [
                "sign"    => ["", "'-'"],
                "thing"   => ["'+'", ""],
                "start"   => ["'+'", "'-'", "'*'"],
                "other"   => ["'*'"],
                "another" => ["'/'"],
            ],
            $grammar,
        );
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

    /**
     * @param string $sourceGrammar
     * @return array<string, string>
     */
    private function calculateFirstSets(string $sourceGrammar): array {
        $grammar = $this->parseString($sourceGrammar);
        return (new FirstSetCalculator($grammar->rules))->calculate();
    }

    private function checkCalculator(array $expected, string $grammar) {
        $this->assertSetsEquals($expected, $this->calculateFirstSets($grammar));
    }

    private function sortRecursive(array $val): array {
        ksort($val);
        $sortedKeys = array_keys($val);
        array_multisort($val);
        $sorted = [];
        foreach ($sortedKeys as $key) {
            $v = $val[$key];
            $sorted[$key] = is_array($v) ? $this->sortRecursive($v) : $v;
        }
        return $sorted;
    }

    private function assertSetsEquals(array $expected, array $actual): void {
        $expectedLen = count($expected);
        $actualLen = count($actual);
        $expected = $this->sortRecursive($expected);
        $actual = $this->sortRecursive($actual);
        $this->assertCount($expectedLen, $expected);
        $this->assertCount($actualLen, $actual);
        $this->assertSame($expected, $actual);
    }
}