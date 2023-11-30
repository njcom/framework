<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\Python;

use Morpho\Compiler\Frontend\Peg\IGrammarNode;
use Morpho\Compiler\Frontend\Peg\Peg;
use Morpho\Testing\TestCase;

class ParserTest extends TestCase {
    public function testExplainGrammarLines() {
        // @todo: move to PEG
        $text = '123';
        $grammarSource = <<<EOF
        @trailer '''
        // some code here
        '''
        file[mod_ty]: a=[statements] ENDMARKER { _PyPegen_make_module(p, a) }
        statements: '123'
        EOF;
        $explainRule = function (IGrammarNode $node) use (&$explainRule) {
            d($node);
        };

        $grammar = Peg::runParser($grammarSource)[0];
        d($grammar);
        $parserFactory = Peg::generateAndEvalParser($grammar)['factory'];
        $ast = Peg::runParser($text, parserFactory: $parserFactory)[0];
        d($ast);
/*        foreach ($grammar->rules as $rule) {
            $explainRule($rule);
        }*/
    }

/*    public function testParser() {
        $grammarSource = <<<EOF
        program: a=[statements] ENDMARKER
        # NOTE: annotated_rhs may start with 'yield'; yield_expr must start with 'yield'
        assignment[stmt_ty]:
            | a=NAME ':' b=expression c=['=' d=annotated_rhs { d }] {
                CHECK_VERSION(
                    stmt_ty,
                    6,
                    "Variable annotation syntax is",
                    _PyAST_AnnAssign(CHECK(expr_ty, _PyPegen_set_expr_context(p, a, Store)), b, c, 1, EXTRA)
                ) }
            | a=('(' b=single_target ')' { b }
                 | single_subscript_attribute_target) ':' b=expression c=['=' d=annotated_rhs { d }] {
                CHECK_VERSION(stmt_ty, 6, "Variable annotations syntax is", _PyAST_AnnAssign(a, b, c, 0, EXTRA)) }
            | a[asdl_expr_seq*]=(z=star_targets '=' { z })+ b=(yield_expr | star_expressions) !'=' tc=[TYPE_COMMENT] {
                 _PyAST_Assign(a, b, NEW_TYPE_COMMENT(p, tc), EXTRA) }
            | a=single_target b=augassign ~ c=(yield_expr | star_expressions) {
                 _PyAST_AugAssign(a, b->kind, c, EXTRA) }
            | invalid_assignment
        EOF;

        $text = '123';
        $grammarSource = <<<EOF
        start: expression
        expression: '123'
        EOF;
        $grammar = Peg::runParser($grammarSource)[0];
        $parserFactory = Peg::generateAndEvalParser($grammar)['factory'];
        d(Peg::runParser($text, parserFactory: $parserFactory)[0]);

    }*/
}