<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\Peg\Peg;
use Morpho\Testing\TestCase;

/**
 * Based on https://github.com/python/cpython/blob/3.12/Lib/test/test_peg_generator/test_pegen.py
 */
class GrammarVisitorTest extends TestCase {
    private GrammarVisitorTest\Visitor $visitor;

    protected function setUp(): void {
        parent::setUp();
        $this->visitor = new GrammarVisitorTest\Visitor();
    }

    public function testParseTrivialGrammar(): void {
        $this->checkNumOfVisitedNodes(6, "start: 'a'");
    }

    public function testParseOrGrammar(): void {
        # Grammar/Rule/Rhs/Alt/NamedItem/NameLeaf   -> 6
        #         Rule/Rhs/                         -> 2
        #                  Alt/NamedItem/StringLeaf -> 3
        #                  Alt/NamedItem/StringLeaf -> 3
        $this->checkNumOfVisitedNodes(
            14,
            <<<EOF
        start: rule
        rule: 'a' | 'b'
        EOF
        );
    }

    public function testParseRepeat1Grammar(): void {
        # Grammar/Rule/Rhs/Alt/NamedItem/Repeat1/StringLeaf -> 6
        $this->checkNumOfVisitedNodes(7, "start: 'a'+");
    }

    public function testParseRepeat0Grammar(): void {
        $grammarSource = "start: 'a'*";
        $grammar = Peg::parseGrammar($grammarSource);

        $this->visitor->visit($grammar);

        # Grammar/Rule/Rhs/Alt/NamedItem/Repeat0/StringLeaf -> 6
        $this->assertSame(7, $this->visitor->numOfVisitedNodes);
    }

    public function testParseOptionalGrammar(): void {
        # Grammar/Rule/Rhs/Alt/NamedItem/StringLeaf                       -> 6
        #                      NamedItem/Opt/Rhs/Alt/NamedItem/Stringleaf -> 6
        $this->checkNumOfVisitedNodes(12, "start: 'a' ['b']");
    }

    private function checkNumOfVisitedNodes(int $expectedNumOfVisitedNodes, string $grammarSource): void {
        $grammar = Peg::parseGrammar($grammarSource);

        $this->visitor->visit($grammar);

        $this->assertSame($expectedNumOfVisitedNodes, $this->visitor->numOfVisitedNodes);
    }
}

namespace Morpho\Test\Unit\Compiler\Frontend\Peg\GrammarVisitorTest;

use Morpho\Compiler\Frontend\Peg\GrammarVisitor;

class Visitor extends GrammarVisitor {
    public int $numOfVisitedNodes = 0;

    public function visit(mixed $node, ...$args): mixed {
        $this->numOfVisitedNodes++;
        parent::visit($node, ...$args);
        return null;
    }
}