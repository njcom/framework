<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Base\NotImplementedException;

/**
 * Based on https://github.com/python/cpython/blob/main/Tools/peg_generator/pegen/first_sets.py
 */
class FirstSetCalculator extends GrammarVisitor {
    private iterable $rules;
    // self.first_sets: Dict[str, Set[str]] = dict()
    private array $firstSets;

    // def __init__(self, rules: Dict[str, Rule]) -> None:
    public function __construct(iterable $rules) {
        $this->rules = $rules;
        $this->nullables = $this->computeNullables($rules);
        $this->firstSets = [];
        //self.in_process: Set[str] = set()
        $this->inProcess = [];
    }

    // def calculate(self) -> Dict[str, Set[str]]:
    public function calculate(): array {
        foreach ($this->rules as $name => $rule) {
            $this->visit($rule);
            return $this->firstSets;
        }
/*
            for name, rule in self.rules.items():
                self.visit(rule)
            return self.first_sets*/

    }

    /**
     * https://github.com/python/cpython/blob/ab71acd67b5b09926498b8c7f855bdb28ac0ec2f/Tools/peg_generator/pegen/parser_generator.py#L287
     * Compute which rules in a grammar are nullable.
     * def compute_nullables(rules: Dict[str, Rule]) -> Set[Any]:
     * @return void
     */
    private function computeNullables(array $rules): array {
        $nullableVisitor = new NullableVisitor($rules);
        foreach ($this->rules as $rule) {
            $nullableVisitor->visit($rule);
        }
        return $nullableVisitor->nullables;
    }

    /*

        def visit_Alt(self, item: Alt) -> Set[str]:
            result: Set[str] = set()
            to_remove: Set[str] = set()
            for other in item.items:
                new_terminals = self.visit(other)
                if isinstance(other.item, NegativeLookahead):
                    to_remove |= new_terminals
                result |= new_terminals
                if to_remove:
                    result -= to_remove
    
                # If the set of new terminals can start with the empty string,
                # it means that the item is completely nullable and we should
                # also considering at least the next item in case the current
                # one fails to parse.
    
                if "" in new_terminals:
                    continue
    
                if not isinstance(other.item, (Opt, NegativeLookahead, Repeat0)):
                    break
    
            # Do not allow the empty string to propagate.
            result.discard("")
    
            return result
    
        def visit_Cut(self, item: Cut) -> Set[str]:
            return set()
    
        def visit_Group(self, item: Group) -> Set[str]:
            return self.visit(item.rhs)
    
        def visit_PositiveLookahead(self, item: Lookahead) -> Set[str]:
            return self.visit(item.node)
    
        def visit_NegativeLookahead(self, item: NegativeLookahead) -> Set[str]:
            return self.visit(item.node)
    
        def visit_NamedItem(self, item: NamedItem) -> Set[str]:
            return self.visit(item.item)
    
        def visit_Opt(self, item: Opt) -> Set[str]:
            return self.visit(item.node)
    
        def visit_Gather(self, item: Gather) -> Set[str]:
            return self.visit(item.node)
    
        def visit_Repeat0(self, item: Repeat0) -> Set[str]:
            return self.visit(item.node)
    
        def visit_Repeat1(self, item: Repeat1) -> Set[str]:
            return self.visit(item.node)
    
        def visit_NameLeaf(self, item: NameLeaf) -> Set[str]:
            if item.value not in self.rules:
                return {item.value}
    
            if item.value not in self.first_sets:
                self.first_sets[item.value] = self.visit(self.rules[item.value])
                return self.first_sets[item.value]
            elif item.value in self.in_process:
                return set()
    
            return self.first_sets[item.value]
    
        def visit_StringLeaf(self, item: StringLeaf) -> Set[str]:
            return {item.value}
    
        def visit_Rhs(self, item: Rhs) -> Set[str]:
            result: Set[str] = set()
            for alt in item.alts:
                result |= self.visit(alt)
            return result
    
        def visit_Rule(self, item: Rule) -> Set[str]:
            if item.name in self.in_process:
                return set()
            elif item.name not in self.first_sets:
                self.in_process.add(item.name)
                terminals = self.visit(item.rhs)
                if item in self.nullables:
                    terminals.add("")
                self.first_sets[item.name] = terminals
                self.in_process.remove(item.name)
            return self.first_sets[item.name]
    */
}