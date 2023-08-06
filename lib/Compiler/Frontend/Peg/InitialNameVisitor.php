<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

/**
 * Based on https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/parser_generator.py
 */
class InitialNameVisitor extends GrammarVisitor {
    /**
     * @var array Dict[str, Rule]
     */
    private array $rules;
    /**
     * @var array Set[Any]
     */
    private array $nullables;

    public function __construct(array $rules) {
        $this->rules = $rules;
        $this->nullables = $this->computeNullables($rules);
    }

    /**
     * @param iterable $node Iterable[Any]
     * @param          ...$args
     * @return array Set[Any]
     */
    protected function genericVisit(iterable $node, ...$args): array {
        // names: Set[str] = set()
        $names = [];
        foreach ($node as $value) {
            if (is_array($value)) { // @todo: replace is_array() with is_iterable()?
                foreach ($value as $item) {
                    $names = array_unique(array_merge($names, $this->visit($item, ...$args)));
                }
            } else {
                $names = array_unique(array_merge($this->visit($value, ...$args)));
            }
        }
        return $names;
    }

    /**
     * @param Alt $alt
     * @return array Set[Any]
     */
    protected function visitAlt(Alt $alt): array {
        $names = []; // names: Set[str] = set()
        foreach ($alt->items as $item) {
            $names = array_unique(array_merge($names, $this->visit($item)));
            if (!in_array($item, $this->nullables, true)) {
                break;
            }
        }
        return $names;
    }

    //def visit_Forced(self, force: Forced) -> Set[Any]:
    protected function visitForced(Forced $force): array {
        return [];
    }

    protected function visit_LookAhead(Lookahead $lookahead): array {
        return [];
    }

    protected function visit_Cut(Cut $cut): array {
        return [];
    }

    // def visit_NameLeaf(self, node: NameLeaf) -> Set[Any]:
    protected function visit_NameLeaf(NameLeaf $leaf): string {
        return $leaf->val;
    }

    protected function visit_StringLeaf(StringLeaf $leaf): array {
        return [];
    }
}