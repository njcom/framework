<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

/**
 * https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/python_generator.py#L48
 */
class InvalidNodeVisitor extends GrammarVisitor {
    protected function visitNameLeaf(NameLeaf $node): bool {
        return str_starts_with($node->val, 'invalid');
    }

    protected function visitStringLeaf(StringLeaf $node): bool {
        return false;
    }

    protected function visitNamedItem(NamedItem $node): bool {
        return $this->visit($node->item);
    }

    protected function visitRhs(Rhs $node): bool {
        foreach ($node->alts as $alt) {
            if ($this->visit($alt)) {
                return true;
            }
        }
        return false;
    }

    protected function visitAlt(Alt $node): bool {
        foreach ($node->items as $item) {
            if ($this->visit($item)) {
                return true;
            }
        }
        return false;
    }

    protected function visitPositiveLookahead(PositiveLookahead $node): bool {
        return $this->lookaheadCallHelper($node);
    }

    protected function visitNegativeLookahead(NegativeLookahead $node): bool {
        //return self.lookahead_call_helper(node)
        return $this->lookaheadCallHelper($node);
    }

    protected function visitOpt(Opt $node): bool {
        return $this->visit($node->node);
    }

    /***
     * @return array Tuple[str, str]
     */
    protected function visitRepeat(Repeat0 $node): array {
        return $this->visit($node->node);
    }

    /**
     * @return array Tuple[str, str]
     */
    protected function visitGather(Gather $node): array {
        return $this->visit($node->node);
    }

    protected function visitGroup(Group $node): bool {
        return $this->visit($node->rhs);
    }

    protected function visitCut(Cut $node): bool {
        return false;
    }

    protected function visitForced(Forced $node): bool {
        return $this->visit($node->node);
    }

    private function lookaheadCallHelper(Lookahead $node): bool {
        return $this->visit($node->node);
    }
}