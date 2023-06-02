<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\IGrammar;
use Morpho\Compiler\Frontend\Peg\Alt;
use Morpho\Compiler\Frontend\Peg\Cut;
use Morpho\Compiler\Frontend\Peg\Forced;
use Morpho\Compiler\Frontend\Peg\Gather;
use Morpho\Compiler\Frontend\Peg\Grammar;
use Morpho\Compiler\Frontend\Peg\Group;
use Morpho\Compiler\Frontend\Peg\IGrammarItem;
use Morpho\Compiler\Frontend\Peg\MetaList;
use Morpho\Compiler\Frontend\Peg\MetaTuple;
use Morpho\Compiler\Frontend\Peg\NamedItem;
use Morpho\Compiler\Frontend\Peg\NamedItemList;
use Morpho\Compiler\Frontend\Peg\NameLeaf;
use Morpho\Compiler\Frontend\Peg\NegativeLookahead;
use Morpho\Compiler\Frontend\Peg\Opt;
use Morpho\Compiler\Frontend\Peg\PositiveLookahead;
use Morpho\Compiler\Frontend\Peg\Repeat0;
use Morpho\Compiler\Frontend\Peg\Repeat1;
use Morpho\Compiler\Frontend\Peg\Rhs;
use Morpho\Compiler\Frontend\Peg\Rule;
use Morpho\Compiler\Frontend\Peg\RuleList;
use Morpho\Compiler\Frontend\Peg\RuleName;
use Morpho\Compiler\Frontend\Peg\StringLeaf;
use Morpho\Testing\TestCase;

class GrammarTest extends TestCase {
    public function testInterface() {
        $grammar = $this->mkEmptyGrammar();
        $this->assertInstanceOf(IGrammar::class, $grammar);
        $this->assertInstanceOf(IGrammarItem::class, $grammar);
        // @todo: use items as a part of the grammar
        $this->assertInstanceOf(IGrammarItem::class, new Rhs([]));
        $this->assertInstanceOf(IGrammarItem::class, new Rule('foo', 'foo', new Rhs([])));
        $this->assertInstanceOf(IGrammarItem::class, new NameLeaf('foo'));
        $this->assertInstanceOf(IGrammarItem::class, new StringLeaf('foo'));
        $this->assertInstanceOf(IGrammarItem::class, new Alt(new NamedItemList()));
        $this->assertInstanceOf(IGrammarItem::class, new NamedItem('foo', new Cut()));
        $this->assertInstanceOf(IGrammarItem::class, new Forced(new StringLeaf('foo')));
        $this->assertInstanceOf(IGrammarItem::class, new PositiveLookahead(new StringLeaf('foo')));
        $this->assertInstanceOf(IGrammarItem::class, new NegativeLookahead(new StringLeaf('foo')));
        $this->assertInstanceOf(IGrammarItem::class, new Opt(new StringLeaf('foo')));
        $this->assertInstanceOf(IGrammarItem::class, new Repeat0(new StringLeaf('foo')));
        $this->assertInstanceOf(IGrammarItem::class, new Repeat1(new StringLeaf('foo')));
        $this->assertInstanceOf(IGrammarItem::class, new Gather(new StringLeaf('foo'), new StringLeaf('foo')));
        $this->assertInstanceOf(IGrammarItem::class, new Group(new Rhs([])));
        $this->assertInstanceOf(IGrammarItem::class, new Cut());
        $this->assertInstanceOf(IGrammarItem::class, new RuleName('foo', null));
        $this->assertInstanceOf(IGrammarItem::class, new RuleName('foo', null));
        $this->assertInstanceOf(IGrammarItem::class, new MetaTuple('foo', null));
        $this->assertInstanceOf(IGrammarItem::class, new MetaList());
        $this->assertInstanceOf(IGrammarItem::class, new RuleList());
        $this->assertInstanceOf(IGrammarItem::class, new NamedItemList());
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

    public function testRepr_NonEmptyGrammar() {
        $this->markTestIncomplete('IGrammarItem::repr()');
    }

    public function testToString() {
        $this->markTestIncomplete('IGrammarItem::__toString()');
    }

    public function testIterator() {
        $this->markTestIncomplete('IGrammarItem::getIterator()');
    }

    private function mkEmptyGrammar(): Grammar {
        $rules = [];
        $metas = [];
        return new Grammar($rules, $metas);
    }
}
