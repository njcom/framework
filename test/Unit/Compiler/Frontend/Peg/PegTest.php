<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\Peg\Grammar;
use Morpho\Compiler\Frontend\Peg\GrammarParser;
use Morpho\Compiler\Frontend\Peg\Token;
use Morpho\Compiler\Frontend\Peg\Tokenizer;
use Morpho\Compiler\Frontend\Peg\Peg;
use Morpho\Compiler\Frontend\Peg\TokenType;
use Morpho\Testing\TestCase;

use const Morpho\Test\BASE_DIR_PATH;

/**
 * https://github.com/python/cpython/blob/3.12/Lib/test/test_peg_generator/test_pegen.py
 */
class PegTest extends TestCase {
    private Peg $parserGen;

    protected function setUp(): void {
        parent::setUp();
        $this->parserGen = new Peg();
    }

    public function testParse() {
        [$grammar, $parser, $tokenizer] = Peg::runParser('foo: bar');
        $this->assertInstanceOf(Grammar::class, $grammar);
        $this->assertInstanceOf(GrammarParser::class, $parser);
        $this->assertInstanceOf(Tokenizer::class, $tokenizer);
    }

    public function testParseGrammar(): void {
        $grammarSource = <<<OUT
        start: sum NEWLINE
        sum: t1=term '+' t2=term { action } | term
        term: NUMBER
        OUT;
        $expected = <<<OUT
        start: sum NEWLINE
        sum: term '+' term | term
        term: NUMBER
        OUT;
        $grammar = Peg::runParser($grammarSource)[0];
        $rules = $grammar->rules;
        $this->assertSame($expected, rtrim($grammar->__toString()));
        $this->assertSame('start: sum NEWLINE', $rules['start']->__toString());
        $this->assertSame("sum: term '+' term | term", $rules['sum']->__toString());
        $this->assertSame("Rule('term', None, Rhs([Alt([NamedItem(None, NameLeaf('NUMBER'))])]))", $rules["term"]->repr());
    }

    public function testLongRuleStr(): void {
        $grammarSource = <<<OUT
        start: zero | one | one zero | one one | one zero zero | one zero one | one one zero | one one one
        OUT;
        $expected = <<<OUT
        start:
            | zero
            | one
            | one zero
            | one one
            | one zero zero
            | one zero one
            | one one zero
            | one one one
        OUT;
        $grammar = Peg::runParser($grammarSource)[0];
        $this->assertSame($expected, $grammar->rules['start']->__toString());
    }

    public function testTypedRules(): void {
        $grammarSource = <<<OUT
        start[int]: sum NEWLINE
        sum[int]: t1=term '+' t2=term { action } | term
        term[int]: NUMBER
        OUT;
        $rules = Peg::runParser($grammarSource)[0]->rules;
        # Check the str() and repr() of a few rules; AST nodes don't support ==.
        $this->assertSame("start: sum NEWLINE", $rules["start"]->__toString());
        $this->assertSame("sum: term '+' term | term", $rules["sum"]->__toString());
        $this->assertSame("Rule('term', 'int', Rhs([Alt([NamedItem(None, NameLeaf('NUMBER'))])]))", $rules['term']->repr());
    }

    public function testGather(): void {
        $grammarSource = <<<OUT
        start: ','.thing+ NEWLINE
        thing: NUMBER
        OUT;
        $grammar = Peg::runParser($grammarSource)[0];
        $rules = $grammar->rules;
        $this->assertSame("start: ','.thing+ NEWLINE", $rules['start']->__toString());
        $this->assertStringStartsWith(
            "Rule('start', None, Rhs([Alt([NamedItem(None, Gather(StringLeaf(\"','\"), NameLeaf('thing'",
            $rules['start']->repr()
        );
        $this->assertSame("thing: NUMBER", $rules["thing"]->__toString());

        $result = Peg::generateAndEvalParser($grammar);
        //$node = $this->testHelper->parseString("42\n", $parserClass);
        // @todo: Check $node representation
        $line = "1, 2\n";
        $node = Peg::runParser($line, parserFactory: $result['factory'])[0];
        $this->assertEquals(
            [
                [
                    new Token(
                        TokenType::NUMBER, val: '1', start: [1, 0], end: [1, 1], line: $line
                    ),
                    new Token(
                        TokenType::NUMBER, val: '2', start: [1, 3], end: [1, 4], line: $line
                    ),
                ],
                new Token(
                    TokenType::NEWLINE, val: "\n", start: [1, 4], end: [1, 5], line: $line
                ),
            ],
            $node
        );
    }

    /*public function testPythonParser(): void {
        d(Peg::parse("start: '<?'", '<?'));

        //fopen(BASE_DIR_PATH . '/lib/Tech/Python/python.token', 'r')
        $stream = fopen(BASE_DIR_PATH . '/lib/Tech/Python/python.gram', 'r');
        $grammar = Peg::runParser($stream)[0];
        d($grammar);
    }*/
    /*
        def test_expr_grammar(self) -> None:
            grammar = """
            start: sum NEWLINE
            sum: term '+' term | term
            term: NUMBER
            """
            parser_class = make_parser(grammar)
            node = parse_string("42\n", parser_class)
            self.assertEqual(
                node,
                [
                    TokenInfo(NUMBER, string="42", start=(1, 0), end=(1, 2), line="42\n"),
                    TokenInfo(NEWLINE, string="\n", start=(1, 2), end=(1, 3), line="42\n"),
                ],
            )

        def test_optional_operator(self) -> None:
            grammar = """
            start: sum NEWLINE
            sum: term ('+' term)?
            term: NUMBER
            """
            parser_class = make_parser(grammar)
            node = parse_string("1 + 2\n", parser_class)
            self.assertEqual(
                node,
                [
                    [
                        TokenInfo(
                            NUMBER, string="1", start=(1, 0), end=(1, 1), line="1 + 2\n"
                        ),
                        [
                            TokenInfo(
                                OP, string="+", start=(1, 2), end=(1, 3), line="1 + 2\n"
                            ),
                            TokenInfo(
                                NUMBER, string="2", start=(1, 4), end=(1, 5), line="1 + 2\n"
                            ),
                        ],
                    ],
                    TokenInfo(
                        NEWLINE, string="\n", start=(1, 5), end=(1, 6), line="1 + 2\n"
                    ),
                ],
            )
            node = parse_string("1\n", parser_class)
            self.assertEqual(
                node,
                [
                    [
                        TokenInfo(NUMBER, string="1", start=(1, 0), end=(1, 1), line="1\n"),
                        None,
                    ],
                    TokenInfo(NEWLINE, string="\n", start=(1, 1), end=(1, 2), line="1\n"),
                ],
            )

        def test_optional_literal(self) -> None:
            grammar = """
            start: sum NEWLINE
            sum: term '+' ?
            term: NUMBER
            """
            parser_class = make_parser(grammar)
            node = parse_string("1+\n", parser_class)
            self.assertEqual(
                node,
                [
                    [
                        TokenInfo(
                            NUMBER, string="1", start=(1, 0), end=(1, 1), line="1+\n"
                        ),
                        TokenInfo(OP, string="+", start=(1, 1), end=(1, 2), line="1+\n"),
                    ],
                    TokenInfo(NEWLINE, string="\n", start=(1, 2), end=(1, 3), line="1+\n"),
                ],
            )
            node = parse_string("1\n", parser_class)
            self.assertEqual(
                node,
                [
                    [
                        TokenInfo(NUMBER, string="1", start=(1, 0), end=(1, 1), line="1\n"),
                        None,
                    ],
                    TokenInfo(NEWLINE, string="\n", start=(1, 1), end=(1, 2), line="1\n"),
                ],
            )

        def test_alt_optional_operator(self) -> None:
            grammar = """
            start: sum NEWLINE
            sum: term ['+' term]
            term: NUMBER
            """
            parser_class = make_parser(grammar)
            node = parse_string("1 + 2\n", parser_class)
            self.assertEqual(
                node,
                [
                    [
                        TokenInfo(
                            NUMBER, string="1", start=(1, 0), end=(1, 1), line="1 + 2\n"
                        ),
                        [
                            TokenInfo(
                                OP, string="+", start=(1, 2), end=(1, 3), line="1 + 2\n"
                            ),
                            TokenInfo(
                                NUMBER, string="2", start=(1, 4), end=(1, 5), line="1 + 2\n"
                            ),
                        ],
                    ],
                    TokenInfo(
                        NEWLINE, string="\n", start=(1, 5), end=(1, 6), line="1 + 2\n"
                    ),
                ],
            )
            node = parse_string("1\n", parser_class)
            self.assertEqual(
                node,
                [
                    [
                        TokenInfo(NUMBER, string="1", start=(1, 0), end=(1, 1), line="1\n"),
                        None,
                    ],
                    TokenInfo(NEWLINE, string="\n", start=(1, 1), end=(1, 2), line="1\n"),
                ],
            )

        def test_repeat_0_simple(self) -> None:
            grammar = """
            start: thing thing* NEWLINE
            thing: NUMBER
            """
            parser_class = make_parser(grammar)
            node = parse_string("1 2 3\n", parser_class)
            self.assertEqual(
                node,
                [
                    TokenInfo(NUMBER, string="1", start=(1, 0), end=(1, 1), line="1 2 3\n"),
                    [
                        TokenInfo(
                            NUMBER, string="2", start=(1, 2), end=(1, 3), line="1 2 3\n"
                        ),
                        TokenInfo(
                            NUMBER, string="3", start=(1, 4), end=(1, 5), line="1 2 3\n"
                        ),
                    ],
                    TokenInfo(
                        NEWLINE, string="\n", start=(1, 5), end=(1, 6), line="1 2 3\n"
                    ),
                ],
            )
            node = parse_string("1\n", parser_class)
            self.assertEqual(
                node,
                [
                    TokenInfo(NUMBER, string="1", start=(1, 0), end=(1, 1), line="1\n"),
                    [],
                    TokenInfo(NEWLINE, string="\n", start=(1, 1), end=(1, 2), line="1\n"),
                ],
            )

        def test_repeat_0_complex(self) -> None:
            grammar = """
            start: term ('+' term)* NEWLINE
            term: NUMBER
            """
            parser_class = make_parser(grammar)
            node = parse_string("1 + 2 + 3\n", parser_class)
            self.assertEqual(
                node,
                [
                    TokenInfo(
                        NUMBER, string="1", start=(1, 0), end=(1, 1), line="1 + 2 + 3\n"
                    ),
                    [
                        [
                            TokenInfo(
                                OP, string="+", start=(1, 2), end=(1, 3), line="1 + 2 + 3\n"
                            ),
                            TokenInfo(
                                NUMBER,
                                string="2",
                                start=(1, 4),
                                end=(1, 5),
                                line="1 + 2 + 3\n",
                            ),
                        ],
                        [
                            TokenInfo(
                                OP, string="+", start=(1, 6), end=(1, 7), line="1 + 2 + 3\n"
                            ),
                            TokenInfo(
                                NUMBER,
                                string="3",
                                start=(1, 8),
                                end=(1, 9),
                                line="1 + 2 + 3\n",
                            ),
                        ],
                    ],
                    TokenInfo(
                        NEWLINE, string="\n", start=(1, 9), end=(1, 10), line="1 + 2 + 3\n"
                    ),
                ],
            )

        def test_repeat_1_simple(self) -> None:
            grammar = """
            start: thing thing+ NEWLINE
            thing: NUMBER
            """
            parser_class = make_parser(grammar)
            node = parse_string("1 2 3\n", parser_class)
            self.assertEqual(
                node,
                [
                    TokenInfo(NUMBER, string="1", start=(1, 0), end=(1, 1), line="1 2 3\n"),
                    [
                        TokenInfo(
                            NUMBER, string="2", start=(1, 2), end=(1, 3), line="1 2 3\n"
                        ),
                        TokenInfo(
                            NUMBER, string="3", start=(1, 4), end=(1, 5), line="1 2 3\n"
                        ),
                    ],
                    TokenInfo(
                        NEWLINE, string="\n", start=(1, 5), end=(1, 6), line="1 2 3\n"
                    ),
                ],
            )
            with self.assertRaises(SyntaxError):
                parse_string("1\n", parser_class)

        def test_repeat_1_complex(self) -> None:
            grammar = """
            start: term ('+' term)+ NEWLINE
            term: NUMBER
            """
            parser_class = make_parser(grammar)
            node = parse_string("1 + 2 + 3\n", parser_class)
            self.assertEqual(
                node,
                [
                    TokenInfo(
                        NUMBER, string="1", start=(1, 0), end=(1, 1), line="1 + 2 + 3\n"
                    ),
                    [
                        [
                            TokenInfo(
                                OP, string="+", start=(1, 2), end=(1, 3), line="1 + 2 + 3\n"
                            ),
                            TokenInfo(
                                NUMBER,
                                string="2",
                                start=(1, 4),
                                end=(1, 5),
                                line="1 + 2 + 3\n",
                            ),
                        ],
                        [
                            TokenInfo(
                                OP, string="+", start=(1, 6), end=(1, 7), line="1 + 2 + 3\n"
                            ),
                            TokenInfo(
                                NUMBER,
                                string="3",
                                start=(1, 8),
                                end=(1, 9),
                                line="1 + 2 + 3\n",
                            ),
                        ],
                    ],
                    TokenInfo(
                        NEWLINE, string="\n", start=(1, 9), end=(1, 10), line="1 + 2 + 3\n"
                    ),
                ],
            )
            with self.assertRaises(SyntaxError):
                parse_string("1\n", parser_class)

        def test_repeat_with_sep_simple(self) -> None:
            grammar = """
            start: ','.thing+ NEWLINE
            thing: NUMBER
            """
            parser_class = make_parser(grammar)
            node = parse_string("1, 2, 3\n", parser_class)
            self.assertEqual(
                node,
                [
                    [
                        TokenInfo(
                            NUMBER, string="1", start=(1, 0), end=(1, 1), line="1, 2, 3\n"
                        ),
                        TokenInfo(
                            NUMBER, string="2", start=(1, 3), end=(1, 4), line="1, 2, 3\n"
                        ),
                        TokenInfo(
                            NUMBER, string="3", start=(1, 6), end=(1, 7), line="1, 2, 3\n"
                        ),
                    ],
                    TokenInfo(
                        NEWLINE, string="\n", start=(1, 7), end=(1, 8), line="1, 2, 3\n"
                    ),
                ],
            )

        def test_left_recursive(self) -> None:
            grammar_source = """
            start: expr NEWLINE
            expr: ('-' term | expr '+' term | term)
            term: NUMBER
            foo: NAME+
            bar: NAME*
            baz: NAME?
            """
            grammar: Grammar = parse_string(grammar_source, GrammarParser)
            parser_class = generate_parser(grammar)
            rules = grammar.rules
            self.assertFalse(rules["start"].left_recursive)
            self.assertTrue(rules["expr"].left_recursive)
            self.assertFalse(rules["term"].left_recursive)
            self.assertFalse(rules["foo"].left_recursive)
            self.assertFalse(rules["bar"].left_recursive)
            self.assertFalse(rules["baz"].left_recursive)
            node = parse_string("1 + 2 + 3\n", parser_class)
            self.assertEqual(
                node,
                [
                    [
                        [
                            TokenInfo(
                                NUMBER,
                                string="1",
                                start=(1, 0),
                                end=(1, 1),
                                line="1 + 2 + 3\n",
                            ),
                            TokenInfo(
                                OP, string="+", start=(1, 2), end=(1, 3), line="1 + 2 + 3\n"
                            ),
                            TokenInfo(
                                NUMBER,
                                string="2",
                                start=(1, 4),
                                end=(1, 5),
                                line="1 + 2 + 3\n",
                            ),
                        ],
                        TokenInfo(
                            OP, string="+", start=(1, 6), end=(1, 7), line="1 + 2 + 3\n"
                        ),
                        TokenInfo(
                            NUMBER, string="3", start=(1, 8), end=(1, 9), line="1 + 2 + 3\n"
                        ),
                    ],
                    TokenInfo(
                        NEWLINE, string="\n", start=(1, 9), end=(1, 10), line="1 + 2 + 3\n"
                    ),
                ],
            )


        def test_python_expr(self) -> None:
            grammar = """
            start: expr NEWLINE? $ { ast.Expression(expr, lineno=1, col_offset=0) }
            expr: ( expr '+' term { ast.BinOp(expr, ast.Add(), term, lineno=expr.lineno, col_offset=expr.col_offset, end_lineno=term.end_lineno, end_col_offset=term.end_col_offset) }
                | expr '-' term { ast.BinOp(expr, ast.Sub(), term, lineno=expr.lineno, col_offset=expr.col_offset, end_lineno=term.end_lineno, end_col_offset=term.end_col_offset) }
                | term { term }
                )
            term: ( l=term '*' r=factor { ast.BinOp(l, ast.Mult(), r, lineno=l.lineno, col_offset=l.col_offset, end_lineno=r.end_lineno, end_col_offset=r.end_col_offset) }
                | l=term '/' r=factor { ast.BinOp(l, ast.Div(), r, lineno=l.lineno, col_offset=l.col_offset, end_lineno=r.end_lineno, end_col_offset=r.end_col_offset) }
                | factor { factor }
                )
            factor: ( '(' expr ')' { expr }
                    | atom { atom }
                    )
            atom: ( n=NAME { ast.Name(id=n.string, ctx=ast.Load(), lineno=n.start[0], col_offset=n.start[1], end_lineno=n.end[0], end_col_offset=n.end[1]) }
                | n=NUMBER { ast.Constant(value=ast.literal_eval(n.string), lineno=n.start[0], col_offset=n.start[1], end_lineno=n.end[0], end_col_offset=n.end[1]) }
                )
            """
            parser_class = make_parser(grammar)
            node = parse_string("(1 + 2*3 + 5)/(6 - 2)\n", parser_class)
            code = compile(node, "", "eval")
            val = eval(code)
            self.assertEqual(val, 3.0)

        def test_nullable(self) -> None:
            grammar_source = """
            start: sign NUMBER
            sign: ['-' | '+']
            """
            grammar: Grammar = parse_string(grammar_source, GrammarParser)
            rules = grammar.rules
            nullables = compute_nullables(rules)
            self.assertNotIn(rules["start"], nullables)  # Not None!
            self.assertIn(rules["sign"], nullables)

        def test_advanced_left_recursive(self) -> None:
            grammar_source = """
            start: NUMBER | sign start
            sign: ['-']
            """
            grammar: Grammar = parse_string(grammar_source, GrammarParser)
            rules = grammar.rules
            nullables = compute_nullables(rules)
            compute_left_recursives(rules)
            self.assertNotIn(rules["start"], nullables)  # Not None!
            self.assertIn(rules["sign"], nullables)
            self.assertTrue(rules["start"].left_recursive)
            self.assertFalse(rules["sign"].left_recursive)

        def test_mutually_left_recursive(self) -> None:
            grammar_source = """
            start: foo 'E'
            foo: bar 'A' | 'B'
            bar: foo 'C' | 'D'
            """
            grammar: Grammar = parse_string(grammar_source, GrammarParser)
            out = io.StringIO()
            genr = PythonParserGenerator(grammar, out)
            rules = grammar.rules
            self.assertFalse(rules["start"].left_recursive)
            self.assertTrue(rules["foo"].left_recursive)
            self.assertTrue(rules["bar"].left_recursive)
            genr.generate("<string>")
            ns: Dict[str, Any] = {}
            exec(out.getvalue(), ns)
            parser_class: Type[Parser] = ns["GeneratedParser"]
            node = parse_string("D A C A E", parser_class)

            self.assertEqual(
                node,
                [
                    [
                        [
                            [
                                TokenInfo(
                                    type=NAME,
                                    string="D",
                                    start=(1, 0),
                                    end=(1, 1),
                                    line="D A C A E",
                                ),
                                TokenInfo(
                                    type=NAME,
                                    string="A",
                                    start=(1, 2),
                                    end=(1, 3),
                                    line="D A C A E",
                                ),
                            ],
                            TokenInfo(
                                type=NAME,
                                string="C",
                                start=(1, 4),
                                end=(1, 5),
                                line="D A C A E",
                            ),
                        ],
                        TokenInfo(
                            type=NAME,
                            string="A",
                            start=(1, 6),
                            end=(1, 7),
                            line="D A C A E",
                        ),
                    ],
                    TokenInfo(
                        type=NAME, string="E", start=(1, 8), end=(1, 9), line="D A C A E"
                    ),
                ],
            )
            node = parse_string("B C A E", parser_class)
            self.assertEqual(
                node,
                [
                    [
                        [
                            TokenInfo(
                                type=NAME,
                                string="B",
                                start=(1, 0),
                                end=(1, 1),
                                line="B C A E",
                            ),
                            TokenInfo(
                                type=NAME,
                                string="C",
                                start=(1, 2),
                                end=(1, 3),
                                line="B C A E",
                            ),
                        ],
                        TokenInfo(
                            type=NAME, string="A", start=(1, 4), end=(1, 5), line="B C A E"
                        ),
                    ],
                    TokenInfo(
                        type=NAME, string="E", start=(1, 6), end=(1, 7), line="B C A E"
                    ),
                ],
            )

        def test_nasty_mutually_left_recursive(self) -> None:
            # This grammar does not recognize 'x - + =', much to my chagrin.
            # But that's the way PEG works.
            # [Breathlessly]
            # The problem is that the toplevel target call
            # recurses into maybe, which recognizes 'x - +',
            # and then the toplevel target looks for another '+',
            # which fails, so it retreats to NAME,
            # which succeeds, so we end up just recognizing 'x',
            # and then start fails because there's no '=' after that.
            grammar_source = """
            start: target '='
            target: maybe '+' | NAME
            maybe: maybe '-' | target
            """
            grammar: Grammar = parse_string(grammar_source, GrammarParser)
            out = io.StringIO()
            genr = PythonParserGenerator(grammar, out)
            genr.generate("<string>")
            ns: Dict[str, Any] = {}
            exec(out.getvalue(), ns)
            parser_class = ns["GeneratedParser"]
            with self.assertRaises(SyntaxError):
                parse_string("x - + =", parser_class)

        def test_lookahead(self) -> None:
            grammar = """
            start: (expr_stmt | assign_stmt) &'.'
            expr_stmt: !(target '=') expr
            assign_stmt: target '=' expr
            expr: term ('+' term)*
            target: NAME
            term: NUMBER
            """
            parser_class = make_parser(grammar)
            node = parse_string("foo = 12 + 12 .", parser_class)
            self.assertEqual(
                node,
                [
                    TokenInfo(
                        NAME, string="foo", start=(1, 0), end=(1, 3), line="foo = 12 + 12 ."
                    ),
                    TokenInfo(
                        OP, string="=", start=(1, 4), end=(1, 5), line="foo = 12 + 12 ."
                    ),
                    [
                        TokenInfo(
                            NUMBER,
                            string="12",
                            start=(1, 6),
                            end=(1, 8),
                            line="foo = 12 + 12 .",
                        ),
                        [
                            [
                                TokenInfo(
                                    OP,
                                    string="+",
                                    start=(1, 9),
                                    end=(1, 10),
                                    line="foo = 12 + 12 .",
                                ),
                                TokenInfo(
                                    NUMBER,
                                    string="12",
                                    start=(1, 11),
                                    end=(1, 13),
                                    line="foo = 12 + 12 .",
                                ),
                            ]
                        ],
                    ],
                ],
            )

        def test_named_lookahead_error(self) -> None:
            grammar = """
            start: foo=!'x' NAME
            """
            with self.assertRaises(SyntaxError):
                make_parser(grammar)

        def test_start_leader(self) -> None:
            grammar = """
            start: attr | NAME
            attr: start '.' NAME
            """
            # Would assert False without a special case in compute_left_recursives().
            make_parser(grammar)

        def test_opt_sequence(self) -> None:
            grammar = """
            start: [NAME*]
            """
            # This case was failing because of a double trailing comma at the end
            # of a line in the generated source. See bpo-41044
            make_parser(grammar)

        def test_left_recursion_too_complex(self) -> None:
            grammar = """
            start: foo
            foo: bar '+' | baz '+' | '+'
            bar: baz '-' | foo '-' | '-'
            baz: foo '*' | bar '*' | '*'
            """
            with self.assertRaises(ValueError) as errinfo:
                make_parser(grammar)
                self.assertTrue("no leader" in str(errinfo.exception.value))

        def test_cut(self) -> None:
            grammar = """
            start: '(' ~ expr ')'
            expr: NUMBER
            """
            parser_class = make_parser(grammar)
            node = parse_string("(1)", parser_class)
            self.assertEqual(
                node,
                [
                    TokenInfo(OP, string="(", start=(1, 0), end=(1, 1), line="(1)"),
                    TokenInfo(NUMBER, string="1", start=(1, 1), end=(1, 2), line="(1)"),
                    TokenInfo(OP, string=")", start=(1, 2), end=(1, 3), line="(1)"),
                ],
            )

        def test_dangling_reference(self) -> None:
            grammar = """
            start: foo ENDMARKER
            foo: bar NAME
            """
            with self.assertRaises(GrammarError):
                parser_class = make_parser(grammar)

        def test_bad_token_reference(self) -> None:
            grammar = """
            start: foo
            foo: NAMEE
            """
            with self.assertRaises(GrammarError):
                parser_class = make_parser(grammar)

        def test_missing_start(self) -> None:
            grammar = """
            foo: NAME
            """
            with self.assertRaises(GrammarError):
                parser_class = make_parser(grammar)

        def test_invalid_rule_name(self) -> None:
            grammar = """
            start: _a b
            _a: 'a'
            b: 'b'
            """
            with self.assertRaisesRegex(GrammarError, "cannot start with underscore: '_a'"):
                parser_class = make_parser(grammar)

        def test_invalid_variable_name(self) -> None:
            grammar = """
            start: a b
            a: _x='a'
            b: 'b'
            """
            with self.assertRaisesRegex(GrammarError, "cannot start with underscore: '_x'"):
                parser_class = make_parser(grammar)

        def test_invalid_variable_name_in_temporal_rule(self) -> None:
            grammar = """
            start: a b
            a: (_x='a' | 'b') | 'c'
            b: 'b'
            """
            with self.assertRaisesRegex(GrammarError, "cannot start with underscore: '_x'"):
                parser_class = make_parser(grammar)

        def test_soft_keyword(self) -> None:
            grammar = """
            start:
                | "number" n=NUMBER { eval(n.string) }
                | "string" n=STRING { n.string }
                | SOFT_KEYWORD l=NAME n=(NUMBER | NAME | STRING) { f"{l.string} = {n.string}"}
            """
            parser_class = make_parser(grammar)
            self.assertEqual(parse_string("number 1", parser_class), 1)
            self.assertEqual(parse_string("string 'b'", parser_class), "'b'")
            self.assertEqual(
                parse_string("number test 1", parser_class), "test = 1"
            )
            assert (
                parse_string("string test 'b'", parser_class) == "test = 'b'"
            )
            with self.assertRaises(SyntaxError):
                parse_string("test 1", parser_class)

        def test_forced(self) -> None:
            grammar = """
            start: NAME &&':' | NAME
            """
            parser_class = make_parser(grammar)
            self.assertTrue(parse_string("number :", parser_class))
            with self.assertRaises(SyntaxError) as e:
                parse_string("a", parser_class)

            self.assertIn("expected ':'", str(e.exception))

        def test_forced_with_group(self) -> None:
            grammar = """
            start: NAME &&(':' | ';') | NAME
            """
            parser_class = make_parser(grammar)
            self.assertTrue(parse_string("number :", parser_class))
            self.assertTrue(parse_string("number ;", parser_class))
            with self.assertRaises(SyntaxError) as e:
                parse_string("a", parser_class)
            self.assertIn("expected (':' | ';')", e.exception.args[0])

        def test_unreachable_explicit(self) -> None:
            source = """
            start: NAME { UNREACHABLE }
            """
            grammar = parse_string(source, GrammarParser)
            out = io.StringIO()
            genr = PythonParserGenerator(
                grammar, out, unreachable_formatting="This is a test"
            )
            genr.generate("<string>")
            self.assertIn("This is a test", out.getvalue())

        def test_unreachable_implicit1(self) -> None:
            source = """
            start: NAME | invalid_input
            invalid_input: NUMBER { None }
            """
            grammar = parse_string(source, GrammarParser)
            out = io.StringIO()
            genr = PythonParserGenerator(
                grammar, out, unreachable_formatting="This is a test"
            )
            genr.generate("<string>")
            self.assertIn("This is a test", out.getvalue())

        def test_unreachable_implicit2(self) -> None:
            source = """
            start: NAME | '(' invalid_input ')'
            invalid_input: NUMBER { None }
            """
            grammar = parse_string(source, GrammarParser)
            out = io.StringIO()
            genr = PythonParserGenerator(
                grammar, out, unreachable_formatting="This is a test"
            )
            genr.generate("<string>")
            self.assertIn("This is a test", out.getvalue())

        def test_unreachable_implicit3(self) -> None:
            source = """
            start: NAME | invalid_input { None }
            invalid_input: NUMBER
            """
            grammar = parse_string(source, GrammarParser)
            out = io.StringIO()
            genr = PythonParserGenerator(
                grammar, out, unreachable_formatting="This is a test"
            )
            genr.generate("<string>")
            self.assertNotIn("This is a test", out.getvalue())

        def test_locations_in_alt_action_and_group(self) -> None:
            grammar = """
            start: t=term NEWLINE? $ { ast.Expression(t, LOCATIONS) }
            term:
                | l=term '*' r=factor { ast.BinOp(l, ast.Mult(), r, LOCATIONS) }
                | l=term '/' r=factor { ast.BinOp(l, ast.Div(), r, LOCATIONS) }
                | factor
            factor:
                | (
                    n=NAME { ast.Name(id=n.string, ctx=ast.Load(), LOCATIONS) } |
                    n=NUMBER { ast.Constant(value=ast.literal_eval(n.string), LOCATIONS) }
                )
            """
            parser_class = make_parser(grammar)
            source = "2*3\n"
            o = ast.dump(parse_string(source, parser_class).body, include_attributes=True)
            p = ast.dump(ast.parse(source).body[0].value, include_attributes=True).replace(
                " kind=None,", ""
            )
            diff = "\n".join(
                difflib.unified_diff(
                    o.split("\n"), p.split("\n"), "cpython", "python-pegen"
                )
            )
            self.assertFalse(diff)
         */
}
