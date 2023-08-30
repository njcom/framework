<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use function Morpho\Base\camelize;
use function Morpho\Base\last;

/**
 * https://github.com/python/cpython/blob/main/Tools/peg_generator/pegen/grammar.py
 */
class GrammarVisitor implements IGrammarVisitor {
    /**
     * Visit a node
     * def visit(self, node: Any, *args: Any, **kwargs: Any) -> Any:
     * @todo: make $args array
     */
    public function visit(mixed $node, ...$args): mixed {
        $method = 'visit' . camelize(last(get_class($node), '\\'), true);
        if (method_exists($this, $method)) {
            return $this->$method($node, ...$args);
        }
        return $this->genericVisit($node, ...$args);
    }

    /**
     * Called if no explicit visitor function exists for a node.
     * def generic_visit(self, node: Iterable[Any], *args: Any, **kwargs: Any) -> Any:
     */
    protected function genericVisit(iterable $node, ...$args): mixed {
        foreach ($node as $value) {
            if (is_array($value) || $value instanceof NamedItemList) { // @todo: replace is_array() with is_iterable()?
                foreach ($value as $item) {
                    $this->visit($item, ...$args);
                }
            } else {
                $this->visit($value, ...$args);
            }
        }
        return null;
    }

    /**
     * https://github.com/python/cpython/blob/ab71acd67b5b09926498b8c7f855bdb28ac0ec2f/Tools/peg_generator/pegen/parser_generator.py#L287
     * Compute which rules in a grammar are nullable.
     * @param array $rules Dict[str, Rule]
     * @return array Set[Any]
     */
    protected function computeNullables(array $rules): array {
        $nullableVisitor = new NullableVisitor($rules);
        foreach ($rules as $rule) {
            $nullableVisitor->visit($rule);
        }
        return $nullableVisitor->nullables;
    }
}