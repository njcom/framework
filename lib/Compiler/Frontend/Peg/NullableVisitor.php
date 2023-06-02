<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

/**
 * https://github.com/python/cpython/blob/ab71acd67b5b09926498b8c7f855bdb28ac0ec2f/Tools/peg_generator/pegen/parser_generator.py#LL221C1-L284C30
 */
class NullableVisitor extends GrammarVisitor {
    // Dict[str, Rule]
    private array $rules;
    //self.nullables: Set[Union[Rule, NamedItem]] = set()
    public array $nullables;
    // self.visited: Set[Any] = set()
    private array $visited = [];

    public function __construct(array $rules) {
        $this->rules = $rules;
        $this->nullables = [];
    }

    protected function visitRule(Rule $rule): bool {
        if (in_array($rule, $this->visited)) {
            return false;
        }
        $this->visited[] = $rule;
        if ($this->visit($rule->rhs)) {
            $this->nullables[] = $rule;
        }
        return in_array($rule, $this->nullables);
    }

    protected function visitRhs(Rhs $rhs): bool {
        foreach ($rhs->alts as $alt) {
            if ($this->visit($alt)) {
                return true;
            }
        }
        return false;
    }

    protected function visitAlt(Alt $alt): bool {
        foreach ($alt->items as $item) {
            if (!$this->visit($item)) {
                return false;
            }
        }
        return true;
    }

    protected function visitForced(Forced $force): bool {
        return true;
    }

    protected function visitLookAhead(Lookahead $lookahead): bool {
        return true;
    }

    protected function visitOpt(Opt $opt): bool {
        return true;
    }

    protected function visitRepeat0(Repeat0 $repeat): bool {
        return true;
    }

    protected function visitRepeat1(Repeat1 $repeat): bool {
        return false;
    }

    protected function visitGather(Gather $gather): bool {
        return false;
    }

    protected function visitCut(Cut $cut): bool {
        return false;
    }

    protected function visitGroup(Group $group): bool {
        return $this->visit($group->rhs);
    }

    protected function visitNamedItem(NamedItem $item): bool {
        if ($this->visit($item->item)) {
            $this->nullables[] = $item;
        }
        return in_array($item, $this->nullables);
    }

    protected function visitNameLeaf(NameLeaf $node): bool {
        if (isset($this->rules[$node->val])) {
            return $this->visit($this->rules[$node->val]);
        }
        // Token or unknown; never empty.
        return false;
    }

    protected function visitStringLeaf(StringLeaf $node): bool {
        // The string token '' is considered empty.
        return $node->val == '';
    }
}