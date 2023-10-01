<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
/**
 * Based on https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/grammar.py
 */
namespace Morpho\Compiler\Frontend\Peg;

use ArrayObject;
use Morpho\Base\NotImplementedException;
use Traversable;

use function Morpho\Base\q;
use function Morpho\Base\qq;

readonly class Grammar implements IGrammarNode {
    /**
     * @var array Dict[str, Rule]
     */
    public array $rules;
    /**
     * @var array Dict[?]
     */
    public array $metas;

    // def __init__(self, rules: Iterable[Rule], metas: Iterable[Tuple[str, Optional[str]]]):
    public function __construct(iterable $rules, iterable $metas) {
        $rulesMap = [];
        foreach ($rules as $rule) {
            $rulesMap[$rule->name] = $rule;
        }
        $this->rules = $rulesMap;
        $this->metas = iterator_to_array($metas);
    }

    public function __toString(): string {
        $res = '';
        foreach ($this->rules as $rule) {
            $res .= $rule->__toString() . "\n";
        }
        return $res;
    }

    public function repr(): string {
        $lines = [
            'Grammar(',
            '  [',
        ];
        foreach ($this->rules as $rule) {
            $lines[] = '    ' . $rule->repr() . ',';
        }
        $lines[] = '  ],';
        $lines[] = '  {repr(list(self.metas.items()))}';
        $lines[] = ')';
        return implode("\n", $lines);
    }

    // def __iter__(self) -> Iterator[Rule]:
    public function getIterator(): Traversable {
        //yield from self.rules.values()
        yield from $this->rules;
    }
}

class Rule implements IGrammarNode, IRenderingActions {
    public readonly string $name;
    public readonly Rhs $rhs;
    public readonly bool $leftRecursive;
    public readonly bool $leader;
    public readonly ?string $type;
    private readonly bool $memo;
    private readonly bool $visited;
    private readonly bool $nullable;
    private bool $renderActions = false;

    // def __init__(self, name: str, type: Optional[str], rhs: Rhs, memo: Optional[object] = None):
    public function __construct(string $name, ?string $type, Rhs $rhs, ?object $memo = null) {
        $this->name = $name;
        $this->type = $type;
        $this->rhs = $rhs;
        $this->memo = (bool)$memo;
        $this->visited = false;
        $this->nullable = false;
        $this->leftRecursive = false;
        $this->leader = false;
    }

    public function isGather(): bool {
        return str_starts_with($this->name, '_gather');
    }

    public function isLoop(): bool {
        return str_starts_with($this->name, '_loop');
    }

    public function __toString(): string {
        if (!$this->renderActions || null === $this->type) {
            $res = "{$this->name}: {$this->rhs}";
        } else {
            $res = "{$this->name}[{$this->type}: {$this->rhs}";
        }
        if (mb_strlen($res) < 88) {
            return $res;
        }
        $lines = [explode(':', $res)[0] . ':'];
        foreach ($this->rhs->alts as $alt) {
            $lines[] = "    | {$alt}";
        }
        return implode("\n", $lines);
    }

    public function repr(): string {
        return 'Rule(' . q($this->name) . ', ' . (null === $this->type ? 'None' : q($this->type)) . ', ' . $this->rhs->repr() . ')';
    }

    // def __iter__(self) -> Iterator[Rhs]
    public function getIterator(): Traversable {
        yield $this->rhs;
    }

    public function renderActions($flag = null): bool {
        if (null !== $flag) {
            $this->renderActions = $flag;
        }
        return $this->renderActions;
    }

    public function flatten(): Rhs {
        // If it's a single parenthesized group, flatten it.
        $rhs = $this->rhs;
        if (!$this->isLoop() && count($rhs->alts) == 1 && count($rhs->alts[0]->items) == 1 && $rhs->alts[0]->items[0]->item instanceof Group) {
            $rhs = $rhs->alts[0]->items[0]->item->rhs;
        }
        return $rhs;
    }
}

abstract readonly class Leaf implements IGrammarNode {
    public string $val;

    public function __construct(string $val) {
        $this->val = $val;
    }

    public function __toString(): string {
        return $this->val;
    }

    // def __iter__(self) -> Iterable[str]:
    public function getIterator(): Traversable {
        if (false) {
            yield;
        }
    }
}

readonly class NameLeaf extends Leaf {
    public function __toString(): string {
        if ($this->val == 'ENDMARKER') {
            return '$';
        }
        return parent::__toString();
    }

    public function repr(): string {
        return 'NameLeaf(' . q($this->val) . ')';
    }
}

// The value is a string literal, including quotes.
readonly class StringLeaf extends Leaf {
    public function repr(): string {
        return 'StringLeaf(' . qq($this->val) . ')';
    }
}

/**
 * Right part of the grammar rule.
 */
readonly class Rhs implements IGrammarNode {
    /**
     * @var array<Alt>
     */
    public array $alts;

    // self.memo: Optional[Tuple[Optional[str], str]] = None

    /**
     * @param array<Alt> $alts
     */
    public function __construct(array $alts) {
        $this->alts = $alts;
    }

    public function __toString(): string {
        $res = [];
        foreach ($this->alts as $alt) {
            $res[] = $alt->__toString();
        }
        return implode(' | ', $res);
    }

    public function repr(): string {
        $items = [];
        foreach ($this->alts as $alt) {
            if ($alt instanceof IGrammarNode) {
                $items[] = $alt->repr();
            } else {
                $items[] = (string)$alt;
            }
        }
        return 'Rhs([' . implode(', ', $items) . '])';
    }

    // def __iter__(self) -> Iterator[List[Alt]]:
    public function getIterator(): Traversable {
        yield $this->alts;
    }
}

class Alt implements IGrammarNode, IRenderingActions {
    public readonly NamedItemList $items;
    public readonly ?string $action;
    private readonly int $icut;
    private bool $renderActions = false;

    // def __init__(self, items: List[NamedItem], *, icut: int = -1, action: Optional[str] = None):
    public function __construct(NamedItemList $items, int $icut = -1, ?string $action = null) {
        $this->items = $items;
        $this->icut = $icut;
        $this->action = $action;
    }

    public function __toString(): string {
        $core = '';
        foreach ($this->items as $item) {
            $core .= ' ' . $item->__toString();
        }
        $core = substr($core, 1);
        if ($this->renderActions && $this->action) {
            return "{$core} {{ {$this->action} }}";
        }
        return $core;
    }

    public function repr(): string {
        $args = [$this->items->repr()];
        if ($this->icut >= 0) {
            $args[] = 'icut=' . $this->icut;
        }
        if ($this->action) {
            $args[] = 'action=' . $this->action;
        }
        return 'Alt(' . implode(', ', $args) . ')';
    }

    //def __iter__(self) -> Iterator[List[NamedItem]]:
    public function getIterator(): Traversable {
        yield $this->items;
    }

    public function renderActions($flag = null): bool {
        if (null !== $flag) {
            $this->renderActions = $flag;
        }
        return $this->renderActions;
    }
}

class NamedItem implements IGrammarNode, IRenderingActions {
    public readonly ?string $name;
    public readonly Leaf|Group|Opt|Repeat|Forced|Lookahead|Rhs|Cut $item;
    private readonly ?string $type;
    private readonly bool $nullable;
    private bool $renderActions = false;

    // def __init__(self, name: Optional[str], item: Item, type: Optional[str] = None): $nullab;e
    public function __construct(?string $name, Leaf|Group|Opt|Repeat|Forced|Lookahead|Rhs|Cut $item, string $type = null) {
        $this->name = $name;
        $this->item = $item;
        $this->type = $type;
        $this->nullable = false;
    }

    public function __toString(): string {
        if ($this->renderActions && $this->name) {
            return "{$this->name}={$this->item}";
        }
        return $this->item->__toString();
    }

    public function repr(): string {
        return 'NamedItem(' . (null === $this->name ? 'None' : q($this->name)) . ', ' . $this->item->repr() . ')';
    }

    /**
     * @return \Traversable Iterator[Item]
     */
    public function getIterator(): Traversable {
        yield $this->item;
    }

    public function renderActions($flag = null): bool {
        if (null !== $flag) {
            $this->renderActions = $flag;
        }
        return $this->renderActions;
    }
}

readonly class Forced implements IGrammarNode {
    public Leaf|Group $node;

    public function __construct(Leaf|Group $node) {
        $this->node = $node;
    }

    public function __toString(): string {
        throw new NotImplementedException();
        //return f"&&{self.node}"
    }

    public function repr(): string {
        throw new NotImplementedException();
    }

    //def __iter__(self) -> Iterator[Plain]:
    public function getIterator(): Traversable {
        yield $this->node;
    }
}

abstract readonly class Lookahead implements IGrammarNode {
    public Leaf|Group $node;
    private string $sign;

    public function __construct(Leaf|Group $node, string $sign) {
        $this->node = $node;
        $this->sign = $sign;
    }

    public function __toString(): string {
        return $this->sign . $this->node;
    }

    public function getIterator(): Traversable {
        yield $this->node;
    }
}

readonly class PositiveLookahead extends Lookahead {
    // def __init__(self, node: Plain):
    public function __construct(Leaf|Group $node) {
        parent::__construct($node, '&');
    }

    public function repr(): string {
        //return f"PositiveLookahead({self.node!r})"
        throw new NotImplementedException();
    }
}

readonly class NegativeLookahead extends Lookahead {
    // def __init__(self, node: Plain):
    public function __construct(Leaf|Group $node) {
        parent::__construct($node, '!');
    }

    public function repr(): string {
        //return f"NegativeLookahead({self.node!r})"
        throw new NotImplementedException();
    }
}

readonly class Opt implements IGrammarNode {
    public Leaf|Group|Opt|Repeat|Forced|Lookahead|Rhs|Cut $node;

    public function __construct(Leaf|Group|Opt|Repeat|Forced|Lookahead|Rhs|Cut $node) {
        $this->node = $node;
    }

    public function __toString(): string {
        /*def __str__(self) -> str:
            s = str(self.node)
            # TODO: Decide whether to use [X] or X? based on type of X
            if " " in s:
                return f"[{s}]"
            else:
                return f"{s}?"*/
        throw new NotImplementedException();
    }

    public function repr(): string {
        throw new NotImplementedException();
        //    return f"Opt({self.node!r})"
    }

    //def __iter__(self) -> Iterator[Item]:
    public function getIterator(): Traversable {
        yield $this->node;
    }
}

// Shared base class for x* and x+.
abstract readonly class Repeat implements IGrammarNode {
    public Leaf|Group $node;

    // self.memo: Optional[Tuple[Optional[str], str]] = None
    private ?array $memo;

    //def __init__(self, node: Plain):
    public function __construct(Leaf|Group $node) {
        $this->node = $node;
        $this->memo = null;
    }

    //def __iter__(self) -> Iterator[Plain]:
    public function getIterator(): Traversable {
        yield $this->node;
    }
}

readonly class Repeat0 extends Repeat {
    public function __toString(): string {
        /*def __str__(self) -> str:
            s = str(self.node)
            # TODO: Decide whether to use (X)* or X* based on type of X
            if " " in s:
                return f"({s})*"
            else:
                return f"{s}*"*/
        throw new NotImplementedException();
    }

    public function repr(): string {
        return 'Repeat0(' . $this->node->repr() . ')';
    }
}

readonly class Repeat1 extends Repeat {
    public function __toString(): string {
        throw new NotImplementedException();
        /*def __str__(self) -> str:
            s = str(self.node)
            # TODO: Decide whether to use (X)+ or X+ based on type of X
            if " " in s:
                return f"({s})+"
            else:
                return f"{s}+"*/
    }

    public function repr(): string {
        return 'Repeat1(' . $this->node->repr() . ')';
    }
}

readonly class Gather extends Repeat {
    public Leaf|Group $separator;

    // def __init__(self, separator: Plain, node: Plain):
    public function __construct(Leaf|Group $separator, Leaf|Group $node) {
        parent::__construct($node);
        $this->separator = $separator;
    }

    public function __toString(): string {
        return $this->separator->__toString() . '.' . $this->node->__toString() . '+';
    }

    public function repr(): string {
        return 'Gather(' . $this->separator->repr() . ', ' . $this->node->repr() . ')';
    }
}

readonly class Group implements IGrammarNode {
    public readonly Rhs $rhs;

    // def __init__(self, rhs: Rhs):
    public function __construct(Rhs $rhs) {
        $this->rhs = $rhs;
    }

    public function __toString(): string {
        //return f"({self.rhs})"
        throw new NotImplementedException();
    }

    public function repr(): string {
        throw new NotImplementedException();
        //return f"Group({self.rhs!r})"
    }

    // def __iter__(self) -> Iterator[Rhs]:
    public function getIterator(): Traversable {
        yield $this->rhs;
    }
}

readonly class Cut implements IGrammarNode {
    public function __toString(): string {
        return '~';
    }

    public function repr(): string {
        //return f"Cut()"
        throw new NotImplementedException();
    }

    //def __iter__(self) -> Iterator[Tuple[str, str]]:
    public function getIterator(): Traversable {
        if (false) {
            yield;
        }
    }
    /*
    def __eq__(self, other: object) -> bool:
        if not isinstance(other, Cut):
            return NotImplemented
        return True

    def initial_names(self) -> AbstractSet[str]:
        return set()
    */
}

/*
Plain = Union[Leaf, Group]
Item = Union[Plain, Opt, Repeat, Forced, Lookahead, Rhs, Cut]
*/

// RuleName = Tuple[str, str]
class RuleName extends ArrayObject implements IGrammarNode {
    public function __construct(string $val, ?string $annotation) {
        parent::__construct([$val, $annotation]);
    }

    public function __toString(): string {
        throw new NotImplementedException();
    }

    public function repr(): string {
        throw new NotImplementedException();
    }
}

// MetaTuple = Tuple[str, Optional[str]]
class MetaTuple extends ArrayObject implements IGrammarNode {
    public function __construct(string $name, ?string $val) {
    }

    public function __toString(): string {
        throw new NotImplementedException();
    }

    public function repr(): string {
        throw new NotImplementedException();
    }
}

// MetaList = List[MetaTuple]
class MetaList extends ArrayObject implements IGrammarNode {
    public function __toString(): string {
        throw new NotImplementedException();
    }

    public function repr(): string {
        throw new NotImplementedException();
    }
}

// RuleList = List[Rule]
class RuleList extends ArrayObject implements IGrammarNode {
    public function __toString(): string {
        throw new NotImplementedException();
    }

    public function repr(): string {
        throw new NotImplementedException();
    }
}

/**
 * NamedItemList = List[NamedItem]
 * PHP doesn't have the Python's behavior for the repr() which calls repr() for child elements, so the ArrayObject is used as replacement.
 */
class NamedItemList extends ArrayObject implements IGrammarNode {
    public function __toString(): string {
        throw new NotImplementedException();
    }

    public function repr(): string {
        $items = [];
        foreach ($this as $val) {
            if ($val instanceof IGrammarNode) {
                $items[] = $val->repr();
            } else {
                $items[] = (string)$val;
            }
        }
        return '[' . implode(', ', $items) . ']';
    }
}

/*
LookaheadOrCut = Union[Lookahead, Forced, Cut]
*/

