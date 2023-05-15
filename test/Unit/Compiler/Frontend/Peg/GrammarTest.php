<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\IGrammar;
use Morpho\Compiler\Frontend\Peg\Grammar;
use Morpho\Compiler\Frontend\Peg\IGrammarItem;
use Morpho\Testing\TestCase;

class GrammarTest extends TestCase {
    public function testInterface() {
        $grammar = $this->mkEmptyGrammar();
        $this->assertInstanceOf(IGrammar::class, $grammar);
        $this->assertInstanceOf(IGrammarItem::class, $grammar);
    }

    public function testRepr_EmptyGrammar() {
        $grammar = $this->mkEmptyGrammar();
        $this->assertSame(
            <<<'OUT'
Grammar(
  [
  ],
  {repr(list(self.metas.items()))}
)
OUT,
            $grammar->repr()
        );
    }

    private function mkEmptyGrammar(): Grammar {
        $rules = [];
        $metas = [];
        return new Grammar($rules, $metas);
    }
}
