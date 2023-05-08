<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
/**
 * The implementation is based on Python's PEG:
 * 1. https://medium.com/@gvanrossum_83706/peg-parsing-series-de5d41b2ed60
 * 2. https://www.python.org/dev/peps/pep-0617/
 * 3. https://www.youtube.com/watch?v=QppWTvh7_sI
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Base\NotImplementedException;
use Morpho\Compiler\Frontend\IGrammar;

/*
class GrammarError(Exception):
    pass


class GrammarVisitor:
    def visit(self, node: Any, *args: Any, **kwargs: Any) -> Any:
        """Visit a node."""
        method = "visit_" + node.__class__.__name__
        visitor = getattr(self, method, self.generic_visit)
        return visitor(node, *args, **kwargs)

    def generic_visit(self, node: Iterable[Any], *args: Any, **kwargs: Any) -> None:
        """Called if no explicit visitor function exists for a node."""
        for value in node:
            if isinstance(value, list):
                for item in value:
                    self.visit(item, *args, **kwargs)
            else:
                self.visit(value, *args, **kwargs)
*/

/*
# Global flag whether we want actions in __str__() -- default off.
SIMPLE_STR = True
*/

readonly class Grammar implements IGrammar {
    private iterable $rules;
    private iterable $metas;

    // def __init__(self, rules: Iterable[Rule], metas: Iterable[Tuple[str, Optional[str]]]):
    public function __construct(iterable $rules, iterable $metas) {
        //self.rules = {rule.name: rule for rule in rules}
        $this->rules = throw new NotImplementedException();
        $this->metas = $metas;
        //self.metas = dict(metas)
    }

    public function rules(): iterable {
        return $this->rules;
    }
/*

def __str__(self) -> str:
    return "\n".join(str(rule) for name, rule in self.rules.items())

def __repr__(self) -> str:
    lines = ["Grammar("]
    lines.append("  [")
    for rule in self.rules.values():
        lines.append(f"    {repr(rule)},")
    lines.append("  ],")
    lines.append("  {repr(list(self.metas.items()))}")
    lines.append(")")
    return "\n".join(lines)

def __iter__(self) -> Iterator[Rule]:
    yield from self.rules.values()
*/
}

readonly class Rule {
    private string $name;
    private ?string $type;
    private Rhs $rhs;
    private ?object $memo;
    private bool $visited;
    private bool $nullable;
    private bool $leftRecursive;
    private bool $leader;

    // def __init__(self, name: str, type: Optional[str], rhs: Rhs, memo: Optional[object] = None):
    public function __construct(string $name, ?string $type, Rhs $rhs, ?object $memo = null) {
        $this->name = $name;
        $this->type = $type;
        $this->rhs = $rhs;
        $this->memo = $memo;
        $this->visited = false;
        $this->nullable = false;
        $this->leftRecursive = false;
        $this->leader = false;
    }
/*
def is_loop(self) -> bool:
    return self.name.startswith("_loop")

def is_gather(self) -> bool:
    return self.name.startswith("_gather")

def __str__(self) -> str:
    if SIMPLE_STR or self.type is None:
        res = f"{self.name}: {self.rhs}"
    else:
        res = f"{self.name}[{self.type}]: {self.rhs}"
    if len(res) < 88:
        return res
    lines = [res.split(":")[0] + ":"]
    lines += [f"    | {alt}" for alt in self.rhs.alts]
    return "\n".join(lines)

def __repr__(self) -> str:
    return f"Rule({self.name!r}, {self.type!r}, {self.rhs!r})"

def __iter__(self) -> Iterator[Rhs]:
    yield self.rhs

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    if self.visited:
        # A left-recursive rule is considered non-nullable.
        return False
    self.visited = True
    self.nullable = self.rhs.nullable_visit(rules)
    return self.nullable

def initial_names(self) -> AbstractSet[str]:
    return self.rhs.initial_names()

def flatten(self) -> Rhs:
    # If it's a single parenthesized group, flatten it.
    rhs = self.rhs
    if (
        not self.is_loop()
        and len(rhs.alts) == 1
        and len(rhs.alts[0].items) == 1
        and isinstance(rhs.alts[0].items[0].item, Group)
    ):
        rhs = rhs.alts[0].items[0].item.rhs
    return rhs

def collect_todo(self, gen: ParserGenerator) -> None:
    rhs = self.flatten()
    rhs.collect_todo(gen)
*/
}

readonly class Leaf {
    private string $val;

    public function __construct(string $val) {
        $this->val = $val;
    }
/*
def __str__(self) -> str:
    return self.value

def __iter__(self) -> Iterable[str]:
    if False:
        yield

@abstractmethod
def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    raise NotImplementedError

@abstractmethod
def initial_names(self) -> AbstractSet[str]:
    raise NotImplementedError
*/
}

readonly class NameLeaf extends Leaf {
/*
"""The value is the name."""

def __str__(self) -> str:
    if self.value == "ENDMARKER":
        return "$"
    return super().__str__()

def __repr__(self) -> str:
    return f"NameLeaf({self.value!r})"

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    if self.value in rules:
        return rules[self.value].nullable_visit(rules)
    # Token or unknown; never empty.
    return False

def initial_names(self) -> AbstractSet[str]:
    return {self.value}
*/
}

readonly class StringLeaf extends Leaf {
/*
"""The value is a string literal, including quotes."""

def __repr__(self) -> str:
    return f"StringLeaf({self.value!r})"

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    # The string token '' is considered empty.
    return not self.value

def initial_names(self) -> AbstractSet[str]:
    return set()
*/
}

readonly class Rhs {
    /**
     * @var array<int, Alt>
     */
    private array $alts;

    // self.memo: Optional[Tuple[Optional[str], str]] = None

    /**
     * def __init__(self, alts: List[Alt]):
     * @param array<int, Alt> $alts
     */
    public function __construct(array $alts) {
        $this->alts = $alts;
    }
/*
def __str__(self) -> str:
    return " | ".join(str(alt) for alt in self.alts)

def __repr__(self) -> str:
    return f"Rhs({self.alts!r})"

def __iter__(self) -> Iterator[List[Alt]]:
    yield self.alts

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    for alt in self.alts:
        if alt.nullable_visit(rules):
            return True
    return False

def initial_names(self) -> AbstractSet[str]:
    names: Set[str] = set()
    for alt in self.alts:
        names |= alt.initial_names()
    return names

def collect_todo(self, gen: ParserGenerator) -> None:
    for alt in self.alts:
        alt.collect_todo(gen)
*/
}

readonly class Alt {
    private NamedItemList $items;
    private int $icut;
    private ?string $action;

    // def __init__(self, items: List[NamedItem], *, icut: int = -1, action: Optional[str] = None):
    public function __construct(NamedItemList $items, int $icut = -1, ?string $action = null) {
        $this->items = $items;
        $this->icut = $icut;
        $this->action = $action;
    }
/*
def __str__(self) -> str:
    core = " ".join(str(item) for item in self.items)
    if not SIMPLE_STR and self.action:
        return f"{core} {{ {self.action} }}"
    else:
        return core

def __repr__(self) -> str:
    args = [repr(self.items)]
    if self.icut >= 0:
        args.append(f"icut={self.icut}")
    if self.action:
        args.append(f"action={self.action!r}")
    return f"Alt({', '.join(args)})"

def __iter__(self) -> Iterator[List[NamedItem]]:
    yield self.items

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    for item in self.items:
        if not item.nullable_visit(rules):
            return False
    return True

def initial_names(self) -> AbstractSet[str]:
    names: Set[str] = set()
    for item in self.items:
        names |= item.initial_names()
        if not item.nullable:
            break
    return names

def collect_todo(self, gen: ParserGenerator) -> None:
    for item in self.items:
        item.collect_todo(gen)
*/
}

readonly class NamedItem {
    private ?string $name;
    private Leaf | Group | Opt | Repeat | Forced | Lookahead | Rhs | Cut $item;
    private ?string $type;
    private bool $nullable;

    // def __init__(self, name: Optional[str], item: Item, type: Optional[str] = None): $nullab;e
    public function __construct(?string $name, Leaf | Group | Opt | Repeat | Forced | Lookahead | Rhs | Cut $item, string $type = null) {
        $this->name = $name;
        $this->item = $item;
        $this->type = $type;
        $this->nullable = false;
    }
/*
def __str__(self) -> str:
    if not SIMPLE_STR and self.name:
        return f"{self.name}={self.item}"
    else:
        return str(self.item)

def __repr__(self) -> str:
    return f"NamedItem({self.name!r}, {self.item!r})"

def __iter__(self) -> Iterator[Item]:
    yield self.item

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    self.nullable = self.item.nullable_visit(rules)
    return self.nullable

def initial_names(self) -> AbstractSet[str]:
    return self.item.initial_names()

def collect_todo(self, gen: ParserGenerator) -> None:
    gen.callmakervisitor.visit(self.item)
*/
}

readonly class Forced {
    private Leaf | Group $node;

    public function __construct(Leaf | Group $node) {
        $this->node = $node;
    }
/*
def __str__(self) -> str:
    return f"&&{self.node}"

def __iter__(self) -> Iterator[Plain]:
    yield self.node

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    return True

def initial_names(self) -> AbstractSet[str]:
    return set()
*/
}

readonly class Lookahead {
    private Leaf | Group $node;
    private string $sign;

    public function __construct(Leaf | Group $node, string $sign) {
        $this->node = $node;
        $this->sign = $sign;
    }
/*
def __str__(self) -> str:
    return f"{self.sign}{self.node}"

def __iter__(self) -> Iterator[Plain]:
    yield self.node

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    return True

def initial_names(self) -> AbstractSet[str]:
    return set()
*/
}

readonly class PositiveLookahead extends Lookahead {
    // def __init__(self, node: Plain):
    public function __construct(Leaf | Group $node) {
        parent::__construct($node, '&');
    }
/*
def __repr__(self) -> str:
    return f"PositiveLookahead({self.node!r})"
*/
}

readonly class NegativeLookahead extends Lookahead {
    // def __init__(self, node: Plain):
    public function __construct(Leaf | Group $node) {
        parent::__construct($node, '!');
    }
/*
def __repr__(self) -> str:
    return f"NegativeLookahead({self.node!r})"
*/
}

readonly class Opt {
    // def __init__(self, node: Item):
    private Leaf | Group | Opt | Repeat | Forced | Lookahead | Rhs | Cut $node;

    public function __construct(Leaf | Group | Opt | Repeat | Forced | Lookahead | Rhs | Cut $node) {
        $this->node = $node;
    }
/*
def __str__(self) -> str:
    s = str(self.node)
    # TODO: Decide whether to use [X] or X? based on type of X
    if " " in s:
        return f"[{s}]"
    else:
        return f"{s}?"

def __repr__(self) -> str:
    return f"Opt({self.node!r})"

def __iter__(self) -> Iterator[Item]:
    yield self.node

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    return True

def initial_names(self) -> AbstractSet[str]:
    return self.node.initial_names()
*/
}

// Shared base class for x* and x+.
readonly class Repeat {
    private Leaf | Group $node;

    // self.memo: Optional[Tuple[Optional[str], str]] = None
    private ?array $memo;

    //def __init__(self, node: Plain):
    public function __construct(Leaf | Group $node) {
        $this->node = $node;
        $this->memo = null;
    }
/*
@abstractmethod
def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    raise NotImplementedError

def __iter__(self) -> Iterator[Plain]:
    yield self.node

def initial_names(self) -> AbstractSet[str]:
    return self.node.initial_names()
*/

}

readonly class Repeat0 extends Repeat {
/*
def __str__(self) -> str:
    s = str(self.node)
    # TODO: Decide whether to use (X)* or X* based on type of X
    if " " in s:
        return f"({s})*"
    else:
        return f"{s}*"

def __repr__(self) -> str:
    return f"Repeat0({self.node!r})"

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    return True
*/
}

readonly class Repeat1 extends Repeat {
/*
def __str__(self) -> str:
    s = str(self.node)
    # TODO: Decide whether to use (X)+ or X+ based on type of X
    if " " in s:
        return f"({s})+"
    else:
        return f"{s}+"

def __repr__(self) -> str:
    return f"Repeat1({self.node!r})"

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    return False
*/
}

readonly class Gather extends Repeat {
    private Leaf | Group $separator;
    private Leaf | Group $node;

    // def __init__(self, separator: Plain, node: Plain):
    public function __construct(Leaf | Group $separator, Leaf | Group $node) {
        $this->separator = $separator;
        $this->node = $node;
    }
/*
def __str__(self) -> str:
    return f"{self.separator!s}.{self.node!s}+"

def __repr__(self) -> str:
    return f"Gather({self.separator!r}, {self.node!r})"

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    return False
*/

}

readonly class Group {
    private Rhs $rhs;

    // def __init__(self, rhs: Rhs):
    public function __construct(Rhs $rhs) {
        $this->rhs = $rhs;
    }

/*
def __str__(self) -> str:
    return f"({self.rhs})"

def __repr__(self) -> str:
    return f"Group({self.rhs!r})"

def __iter__(self) -> Iterator[Rhs]:
    yield self.rhs

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
    return self.rhs.nullable_visit(rules)

def initial_names(self) -> AbstractSet[str]:
    return self.rhs.initial_names()
*/
}

readonly class Cut {
/*
def __repr__(self) -> str:
    return f"Cut()"

def __str__(self) -> str:
    return f"~"

def __iter__(self) -> Iterator[Tuple[str, str]]:
    if False:
        yield

def __eq__(self, other: object) -> bool:
    if not isinstance(other, Cut):
        return NotImplemented
    return True

def nullable_visit(self, rules: Dict[str, Rule]) -> bool:
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
readonly class RuleName {
    private ?string $val;
    private ?string $annotation;

    public function __construct(?string $val, ?string $annotation) {
        $this->val = $val;
        $this->annotation = $annotation;
    }
}

// MetaTuple = Tuple[str, Optional[str]]
readonly class MetaTuple {

}

// MetaList = List[MetaTuple]
class MetaList extends \ArrayObject {
}

// RuleList = List[Rule]
class RuleList extends \ArrayObject {
}

// NamedItemList = List[NamedItem]
class NamedItemList extends \ArrayObject {
}

/*
LookaheadOrCut = Union[Lookahead, Forced, Cut]
*/

