<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

/**
 * Visitor that invokes a provided callmaker visitor with just the NamedItem nodes
 * https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/parser_generator.py#L43
 */
class RuleCollectorVisitor extends GrammarVisitor {
    /**
     * @var array Dict[str, Rule]
     */
    private array $rules;

    private GrammarVisitor $callMaker;

    public function __construct(array $rules, GrammarVisitor $callMakerVisitor) {
        $this->rules = $rules;
        $this->callMaker = $callMakerVisitor;
    }

    protected function visitRule(Rule $rule): void {
        $this->visit($rule->flatten());
    }

    protected function visitNamedItem(NamedItem $item): void {
        $this->callMaker->visit($item);
    }
}