<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

class RuleCollectorVisitor {
    public function __construct($rules, \Morpho\Compiler\Frontend\Peg\GrammarVisitor $callMakerVisitor) {
    }
/*
    class RuleCollectorVisitor(GrammarVisitor):
        """Visitor that invokes a provieded callmaker visitor with just the NamedItem nodes"""

        def __init__(self, rules: Dict[str, Rule], callmakervisitor: GrammarVisitor) -> None:
            self.rulses = rules
            self.callmaker = callmakervisitor

        def visit_Rule(self, rule: Rule) -> None:
            self.visit(rule.flatten())

        def visit_NamedItem(self, item: NamedItem) -> None:
            self.callmaker.visit(item)
 */
}