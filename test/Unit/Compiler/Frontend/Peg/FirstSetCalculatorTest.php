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

/*
    def test_optionals(self) -> None:
        grammar = """
            start: expr NEWLINE
            expr: ['a'] ['b'] 'c'
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {
                "expr": {"'c'", "'a'", "'b'"},
                "start": {"'c'", "'a'", "'b'"},
            },
        )

    def test_repeat_with_separator(self) -> None:
        grammar = """
        start: ','.thing+ NEWLINE
        thing: NUMBER
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {"thing": {"NUMBER"}, "start": {"NUMBER"}},
        )

    def test_optional_operator(self) -> None:
        grammar = """
        start: sum NEWLINE
        sum: (term)? 'b'
        term: NUMBER
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {
                "term": {"NUMBER"},
                "sum": {"NUMBER", "'b'"},
                "start": {"'b'", "NUMBER"},
            },
        )

    def test_optional_literal(self) -> None:
        grammar = """
        start: sum NEWLINE
        sum: '+' ? term
        term: NUMBER
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {
                "term": {"NUMBER"},
                "sum": {"'+'", "NUMBER"},
                "start": {"'+'", "NUMBER"},
            },
        )

    def test_optional_after(self) -> None:
        grammar = """
        start: term NEWLINE
        term: NUMBER ['+']
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {"term": {"NUMBER"}, "start": {"NUMBER"}},
        )

    def test_optional_before(self) -> None:
        grammar = """
        start: term NEWLINE
        term: ['+'] NUMBER
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {"term": {"NUMBER", "'+'"}, "start": {"NUMBER", "'+'"}},
        )

    def test_repeat_0(self) -> None:
        grammar = """
        start: thing* "+" NEWLINE
        thing: NUMBER
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {"thing": {"NUMBER"}, "start": {'"+"', "NUMBER"}},
        )

    def test_repeat_0_with_group(self) -> None:
        grammar = """
        start: ('+' '-')* term NEWLINE
        term: NUMBER
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {"term": {"NUMBER"}, "start": {"'+'", "NUMBER"}},
        )

    def test_repeat_1(self) -> None:
        grammar = """
        start: thing+ '-' NEWLINE
        thing: NUMBER
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {"thing": {"NUMBER"}, "start": {"NUMBER"}},
        )

    def test_repeat_1_with_group(self) -> None:
        grammar = """
        start: ('+' term)+ term NEWLINE
        term: NUMBER
        """
        self.assertEqual(
            self.calculate_first_sets(grammar), {"term": {"NUMBER"}, "start": {"'+'"}}
        )

    def test_gather(self) -> None:
        grammar = """
        start: ','.thing+ NEWLINE
        thing: NUMBER
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {"thing": {"NUMBER"}, "start": {"NUMBER"}},
        )

    def test_positive_lookahead(self) -> None:
        grammar = """
        start: expr NEWLINE
        expr: &'a' opt
        opt: 'a' | 'b' | 'c'
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {
                "expr": {"'a'"},
                "start": {"'a'"},
                "opt": {"'b'", "'c'", "'a'"},
            },
        )

    def test_negative_lookahead(self) -> None:
        grammar = """
        start: expr NEWLINE
        expr: !'a' opt
        opt: 'a' | 'b' | 'c'
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {
                "opt": {"'b'", "'a'", "'c'"},
                "expr": {"'b'", "'c'"},
                "start": {"'b'", "'c'"},
            },
        )

    def test_left_recursion(self) -> None:
        grammar = """
        start: expr NEWLINE
        expr: ('-' term | expr '+' term | term)
        term: NUMBER
        foo: 'foo'
        bar: 'bar'
        baz: 'baz'
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {
                "expr": {"NUMBER", "'-'"},
                "term": {"NUMBER"},
                "start": {"NUMBER", "'-'"},
                "foo": {"'foo'"},
                "bar": {"'bar'"},
                "baz": {"'baz'"},
            },
        )

    def test_advance_left_recursion(self) -> None:
        grammar = """
        start: NUMBER | sign start
        sign: ['-']
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {"sign": {"'-'", ""}, "start": {"'-'", "NUMBER"}},
        )

    def test_mutual_left_recursion(self) -> None:
        grammar = """
        start: foo 'E'
        foo: bar 'A' | 'B'
        bar: foo 'C' | 'D'
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {
                "foo": {"'D'", "'B'"},
                "bar": {"'D'"},
                "start": {"'D'", "'B'"},
            },
        )

    def test_nasty_left_recursion(self) -> None:
        # TODO: Validate this
        grammar = """
        start: target '='
        target: maybe '+' | NAME
        maybe: maybe '-' | target
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {"maybe": set(), "target": {"NAME"}, "start": {"NAME"}},
        )

    def test_nullable_rule(self) -> None:
        grammar = """
        start: sign thing $
        sign: ['-']
        thing: NUMBER
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {
                "sign": {"", "'-'"},
                "thing": {"NUMBER"},
                "start": {"NUMBER", "'-'"},
            },
        )

    def test_epsilon_production_in_start_rule(self) -> None:
        grammar = """
        start: ['-'] $
        """
        self.assertEqual(
            self.calculate_first_sets(grammar), {"start": {"ENDMARKER", "'-'"}}
        )

    def test_multiple_nullable_rules(self) -> None:
        grammar = """
        start: sign thing other another $
        sign: ['-']
        thing: ['+']
        other: '*'
        another: '/'
        """
        self.assertEqual(
            self.calculate_first_sets(grammar),
            {
                "sign": {"", "'-'"},
                "thing": {"'+'", ""},
                "start": {"'+'", "'-'", "'*'"},
                "other": {"'*'"},
                "another": {"'/'"},
            },
        )
 */

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
}