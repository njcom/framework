<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Testing\TestCase;

/**
 * Based on
 * https://github.com/python/cpython/blob/3.12/Lib/test/test_peg_generator/test_pegen.py
 */
class GrammarVisitorTest extends TestCase {
/*
class TestGrammarVisitor:
    class Visitor(GrammarVisitor):
        def __init__(self) -> None:
            self.n_nodes = 0

        def visit(self, node: Any, *args: Any, **kwargs: Any) -> None:
            self.n_nodes += 1
            super().visit(node, *args, **kwargs)

    def test_parse_trivial_grammar(self) -> None:
        grammar = """
        start: 'a'
        """
        rules = parse_string(grammar, GrammarParser)
        visitor = self.Visitor()

        visitor.visit(rules)

        self.assertEqual(visitor.n_nodes, 6)

    def test_parse_or_grammar(self) -> None:
        grammar = """
        start: rule
        rule: 'a' | 'b'
        """
        rules = parse_string(grammar, GrammarParser)
        visitor = self.Visitor()

        visitor.visit(rules)

        # Grammar/Rule/Rhs/Alt/NamedItem/NameLeaf   -> 6
        #         Rule/Rhs/                         -> 2
        #                  Alt/NamedItem/StringLeaf -> 3
        #                  Alt/NamedItem/StringLeaf -> 3

        self.assertEqual(visitor.n_nodes, 14)

    def test_parse_repeat1_grammar(self) -> None:
        grammar = """
        start: 'a'+
        """
        rules = parse_string(grammar, GrammarParser)
        visitor = self.Visitor()

        visitor.visit(rules)

        # Grammar/Rule/Rhs/Alt/NamedItem/Repeat1/StringLeaf -> 6
        self.assertEqual(visitor.n_nodes, 7)

    def test_parse_repeat0_grammar(self) -> None:
        grammar = """
        start: 'a'*
        """
        rules = parse_string(grammar, GrammarParser)
        visitor = self.Visitor()

        visitor.visit(rules)

        # Grammar/Rule/Rhs/Alt/NamedItem/Repeat0/StringLeaf -> 6

        self.assertEqual(visitor.n_nodes, 7)

    def test_parse_optional_grammar(self) -> None:
        grammar = """
        start: 'a' ['b']
        """
        rules = parse_string(grammar, GrammarParser)
        visitor = self.Visitor()

        visitor.visit(rules)

        # Grammar/Rule/Rhs/Alt/NamedItem/StringLeaf                       -> 6
        #                      NamedItem/Opt/Rhs/Alt/NamedItem/Stringleaf -> 6

        self.assertEqual(visitor.n_nodes, 12)


 */
}