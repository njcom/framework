<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

/**
 * Based on https://github.com/python/cpython/blob/main/Tools/peg_generator/pegen/first_sets.py
 */
class FirstSetCalculator extends GrammarVisitor {
    /**
     * @var array $rules Dict[str, Rule]
     */
    private array $rules;

    /**
     * @var array Dict[str, Set[str]]
     */
    private array $firstSets;

    /**
     * NB: Set[Any] in Python
     * @var array Dict[str, true]
     */
    private array $inProcess;

    /**
     * @var array ?
     */
    private array $nullables;

    /**
     * @param array $rules Dict[str, Rule]
     */
    public function __construct(array $rules) {
        $this->rules = $rules;
        $this->nullables = $this->computeNullables($rules);
        $this->firstSets = [];
        $this->inProcess = [];
    }

    /**
     * @return array Dict[str, Set[str]]
     */
    public function calculate(): array {
        foreach ($this->rules as $rule) {
            $this->visit($rule);
        }
        return $this->firstSets;
    }

    /**
     * https://github.com/python/cpython/blob/ab71acd67b5b09926498b8c7f855bdb28ac0ec2f/Tools/peg_generator/pegen/parser_generator.py#L287
     * Compute which rules in a grammar are nullable.
     * @param array $rules Dict[str, Rule]
     * @return array Set[Any]
     */
    private function computeNullables(array $rules): array {
        $nullableVisitor = new NullableVisitor($rules);
        foreach ($rules as $rule) {
            $nullableVisitor->visit($rule);
        }
        return $nullableVisitor->nullables;
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\Alt $item
     * @return array Set[str]
     */
    protected function visitAlt(Alt $item): array {
        $result = [];
        $toRemove = [];
        foreach ($item->items as $other) {
            $newTerminals = $this->visit($other);
            if ($other->item instanceof NegativeLookahead) {
                $toRemove = array_unique(array_merge($toRemove, $newTerminals));
            }
            $result = array_unique(array_merge($result, $newTerminals));
            if ($toRemove) {
                $result = array_values(array_diff($result, $toRemove));
            }

            // If the set of new terminals can start with the empty string,
            // it means that the item is completely nullable and we should
            // also considering at least the next item in case the current
            // one fails to parse.

            if (in_array('', $newTerminals)) {
                continue;
            }
            if (!($other->item instanceof Opt || $other->item instanceof NegativeLookahead || $other->item instanceof Repeat0)) {
                break;
            }
        }
        // Do not allow the empty string to propagate.
        return array_values(array_filter($result, fn($val) => $val !== ''));
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\Cut $item
     * @return array Set[str]
     */
    protected function visitCut(Cut $item): array {
        return [];
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\Group $item
     * @return array Set[str]
     */
    protected function visitGroup(Group $item): array {
        return $this->visit($item->rhs);
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\Lookahead $item
     * @return array Set[str]
     */
    protected function visitPositiveLookahead(Lookahead $item): array {
        return $this->visit($item->node);
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\NegativeLookahead $item
     * @return array Set[str]
     */
    protected function visitNegativeLookahead(NegativeLookahead $item): array {
        return $this->visit($item->node);
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\NamedItem $item
     * @return array Set[str]
     */
    protected function visitNamedItem(NamedItem $item): array {
        return $this->visit($item->item);
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\Opt $item
     * @return array Set[str]
     */
    protected function visitOpt(Opt $item): array {
        return $this->visit($item->node);
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\Gather $item
     * @return array Set[str]
     */
    protected function visitGather(Gather $item): array {
        return $this->visit($item->node);
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\Repeat0 $item
     * @return array Set[str]
     */
    protected function visitRepeat0(Repeat0 $item): array {
        return $this->visit($item->node);
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\Repeat1 $item
     * @return array Set[str]
     */
    protected function visitRepeat1(Repeat1 $item): array {
        return $this->visit($item->node);
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\NameLeaf $item
     * @return array Set[str]
     */
    protected function visitNameLeaf(NameLeaf $item): array {
        if (!isset($this->rules[$item->val])) {
            return [$item->val];
        }
        if (!isset($this->firstSets[$item->val])) {
            $this->firstSets[$item->val] = $this->visit($this->rules[$item->val]);
        } elseif (isset($this->inProcess[$item->val])) {
            return [];
        }
        return $this->firstSets[$item->val];
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\StringLeaf $item
     * @return array Set[str]
     */
    protected function visitStringLeaf(StringLeaf $item): array {
        return [$item->val];
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\Rhs $item
     * @return array Set[str]
     */
    protected function visitRhs(Rhs $item): array {
        $result = [];
        foreach ($item->alts as $alt) {
            $result = array_unique(array_merge($result, $this->visit($alt)));
        }
        return $result;
    }

    /**
     * @param \Morpho\Compiler\Frontend\Peg\Rule $item
     * @return array Set[str]
     */
    protected function visitRule(Rule $item): array {
        if (isset($this->inProcess[$item->name])) {
            return [];
        }
        if (!isset($this->firstSets[$item->name])) {
            $this->inProcess[$item->name] = true;
            $terminals = $this->visit($item->rhs);
            if (in_array($item, $this->nullables)) {
                $terminals[] = '';
                $terminals = array_unique($terminals);
            }
            $this->firstSets[$item->name] = $terminals;
            unset($this->inProcess[$item->name]);
        }
        return $this->firstSets[$item->name];
    }
}