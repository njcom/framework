<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;
use Morpho\Base\NotImplementedException;

/**
 * https://github.com/python/cpython/blob/main/Tools/peg_generator/pegen/grammar_parser.py
 */
class GrammarParser extends Parser {
    public function __invoke(mixed $val): mixed {
        throw new NotImplementedException();
    }

    public function start(): ?Grammar {
        return $this->memoize(
            __METHOD__,
            function (): ?Grammar {
                # start: grammar $
                $index = $this->index();
                if (($grammar = $this->grammar()) && $this->expect('ENDMARKER')) {
                    return $grammar;
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function grammar(): ?Grammar {
        return $this->memoize(
            __METHOD__,
            function (): ?Grammar {
                # grammar: metas rules | rules
                $index = $this->index();
                if (($metas = $this->metas()) && ($rules = $this->rules())) {
                    return new Grammar($rules, $metas);
                }
                $this->reset($index);
                if ($rules = $this->rules()) {
                    return new Grammar($rules, []);
                }
                $this->reset($index);
                return null;
            }
        );
    }


    private function metas(): ?MetaList {
        return $this->memoize(
            __METHOD__,
            function (): ?MetaList {
                # metas: meta metas | meta
                $mark = $this->index();
                if (($meta = $this->meta()) && ($metas = $this->metas())) {
                    return new MetaList(array_merge([$meta], $metas));
                }
                $this->reset($mark);
                if ($meta = $this->meta()) {
                    return new MetaList([$meta]);
                }
                $this->reset($mark);
                return null;
            }
        );
    }

    private function meta(): ?MetaTuple {
        return $this->memoize(
            __METHOD__,
            function (): ?MetaTuple {
                # meta: "@" NAME NEWLINE | "@" NAME NAME NEWLINE | "@" NAME STRING NEWLINE
                $index = $this->index();
                if ($this->expect('@') && ($name = $this->name()) && $this->expect('NEWLINE')) {
                    return new MetaTuple($name->val, null);
                }
                $this->reset($index);
                if ($this->expect('@') && ($a = $this->name()) && ($b = $this->name()) && $this->expect('NEWLINE')) {
                    return new MetaTuple($a->val, $b->val);
                }
                $this->reset($index);
                if ($this->expect('@') && ($name = $this->name()) && ($string = $this->string()) && $this->expect('NEWLINE')) {
                    return new MetaTuple($name->val, Ast::literalEval($string->val));
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function rules(): ?RuleList {
        return $this->memoize(
            __METHOD__,
            function (): ?RuleList {
                # rules: rule rules | rule
                $index = $this->index();
                if (($rule = $this->rule()) && ($rules = $this->rules())) {
                    return new RuleList(array_merge([$rule], $rules->getArrayCopy()));
                }
                $this->reset($index);
                if ($rule = $this->rule()) {
                    return new RuleList($rule);
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function rule(): ?Rule {
        return $this->memoize(
            __METHOD__,
            function (): ?Rule {
                # rule: rulename memoflag? ":" alts NEWLINE INDENT more_alts DEDENT | rulename memoflag? ":" NEWLINE INDENT more_alts DEDENT | rulename memoflag? ":" alts NEWLINE
                $index = $this->index();
                if (
                    ($ruleName = $this->ruleName())
                    && ($opt = $this->memoFlag())
                    && ($this->expect(":"))
                    && ($alts = $this->alts())
                    && ($this->expect('NEWLINE'))
                    && ($this->expect('INDENT'))
                    && ($moreAlts = $this->moreAlts())
                    && ($this->expect('DEDENT'))
                ) {
                    return new Rule($ruleName[0], $ruleName[1], new Rhs(array_merge($alts->alts, $moreAlts->alts)), memo: $opt);
                }
                $this->reset($index);
                if (
                    ($ruleName = $this->ruleName())
                    && ($opt = $this->memoFlag())
                    && ($this->expect(":"))
                    && ($this->expect('NEWLINE'))
                    && ($this->expect('INDENT'))
                    && ($moreAlts = $this->moreAlts())
                    && ($this->expect('DEDENT'))
                ) {
                    return new Rule($ruleName[0], $ruleName[1], $moreAlts, memo: $opt);
                }
                $this->reset($index);
                if (
                    ($ruleName = $this->ruleName())
                    && ($opt = $this->memoFlag())
                    && ($this->expect(":"))
                    && ($alts = $this->alts())
                    && ($this->expect('NEWLINE'))
                ) {
                    return new Rule($ruleName[0], $ruleName[1], $alts, memo: $opt);
                }
                $this->reset($index);
                return null;
            }
        );
    }

    /**
     * @return array|null Tuple[str, str]
     */
    private function ruleName(): ?RuleName {
        return $this->memoize(
            __METHOD__,
            function (): ?RuleName {
                # rulename: NAME annotation | NAME
                $index = $this->index();
                if (($name = $this->name()) && ($annotation = $this->annotation())) {
                    return new RuleName($name->val, $annotation);
                }
                $this->reset($index);
                if ($name = $this->name()) {
                    return new RuleName($name->val, null);
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function memoFlag(): ?string {
        return $this->memoize(
            __METHOD__,
            function (): ?string {
                # memoflag: '(' "memo" ')'
                $index = $this->index();
                if ($this->expect('(') && $this->expect('memo') && $this->expect(')')) {
                    return "memo";
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function alts(): ?Rhs {
        return $this->memoize(
            __METHOD__,
            function (): ?Rhs {
                # alts: alt "|" alts | alt
                $index = $this->index();
                if (($alt = $this->alt()) && $this->expect("|") && ($alts = $this->alts())) {
                    return new Rhs(array_merge([$alt], $alts->alts));
                }
                if ($alt = $this->alt()) {
                    return new Rhs([$alt]);
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function moreAlts(): ?Rhs {
        return $this->memoize(
            __METHOD__,
            function (): ?Rhs {
                # more_alts: "|" alts NEWLINE more_alts | "|" alts NEWLINE
                $index = $this->index();
                if (
                    ( $this->expect("|"))
                    && ($alts = $this->alts())
                    && $this->expect('NEWLINE')
                    && ($more_alts = $this->more_alts())
                ) {
                    return new Rhs(array_merge($alts->alts, $more_alts->alts));
                }
                $this->reset($index);
                if ($this->expect("|") && ($alts = $this->alts())  &&($this->expect('NEWLINE'))) {
                    return new Rhs($alts->alts);
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function alt(): ?Alt {
        return $this->memoize(
            __METHOD__,
            function (): ?Alt {
                # alt: items '$' action | items '$' | items action | items
                $index = $this->index();
                if (
                    ($items = $this->items())
                    && ($this->expect('$'))
                    && ($action = $this->action())
                ) {
                    return new Alt(array_merge($items, [new NamedItem(null, new NameLeaf('ENDMARKER'))]), action: $action);
                }
                $this->reset($index);
                if (
                    ($items = $this->items())
                    && ($this->expect('$'))
                ) {
                    return new Alt(array_merge($items, [new NamedItem(null, new NameLeaf('ENDMARKER'))]), action: null);
                }
                $this->reset($index);
                if (
                    ($items = $this->items())
                    && ($action = $this->action())) {
                    return new Alt($items, action: $action);
                }
                $this->reset($index);
                if ($items = $this->items()) {
                    return new Alt($items, action: null);
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function items(): ?NamedItemList {
        return $this->memoize(
            __METHOD__,
            function (): ?NamedItemList {
                # items: named_item items | named_item
                $index = $this->index();
                if (($named_item = $this->namedItem()) && ($items = $this->items())) {
                    return new NamedItemList(array_merge([$named_item], $items));
                }
                $this->reset($index);
                if ($named_item = $this->namedItem()) {
                    return new NamedItemList([$named_item]);
                }
                $this->reset($index);
                return null;
            }
        );
    }

    function namedItem(): ?NamedItem {
        return $this->memoize(
            __METHOD__,
            function (): ?NamedItem {
                # named_item: NAME annotation '=' ~ item | NAME '=' ~ item | item | forced_atom | lookahead
                $index = $this->index();
                $cut = false;
                if (
                    ($name = $this->name())
                    && ($annotation = $this->annotation())
                    && ($this->expect('='))
                    && ($cut = true)
                    && ($item = $this->item())
                ) {
                    return new NamedItem($name->val, $item, $annotation);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                //$cut = false;
                if (($name = $this->name())
                    && $this->expect('=')
                    && ($cut = true)
                    && ($item = $this->item())) {
                    return new NamedItem($name->val, $item);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                if ($item = $this->item()) {
                    return new NamedItem(null, $item);
                }
                $this->reset($index);
                if ($it = $this->forcedAtom()) {
                    return new NamedItem(null, $it);
                }
                $this->reset($index);
                if ($it = $this->lookahead()) {
                    return new NamedItem(null, $it);
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function forcedAtom(): null | Lookahead | Forced | Cut {
        return $this->memoize(
            __METHOD__,
            function (): null | Lookahead | Forced | Cut {
                # forced_atom: '&' '&' ~ atom
                $index = $this->index();
                //$cut = false;
                if ($this->expect('&') && $this->expect('&') && /*($cut = true) &&*/ ($atom = $this->atom())) {
                    return new Forced($atom);
                }
                $this->reset($index);
//                if ($cut) {
//                    return null
//                }
                return null;
            }
        );
    }

    // def lookahead(self) -> Optional[LookaheadOrCut]:
    // LookaheadOrCut = Union[Lookahead, Forced, Cut]
    private function lookahead(): null | Lookahead | Forced | Cut {
        return $this->memoize(
            __METHOD__,
            function (): null | Lookahead | Forced | Cut {
                # lookahead: '&' ~ atom | '!' ~ atom | '~'
                $index = $this->index();
                $cut = false;
                if ( $this->expect('&') && ($cut = true) && ($atom = $this->atom())) {
                    return new PositiveLookahead($atom);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                //$cut = false;
                if ($this->expect('!') && ($cut = true) && ($atom = $this->atom())) {
                    return new NegativeLookahead($atom);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                if ($this->expect('~')) {
                    return new Cut();
                }
                $this->reset($index);
                return null;
            }
        );
    }

    // def item(self) -> Optional[Item]
    // Item = Union[Plain, Opt, Repeat, Forced, Lookahead, Rhs, Cut]
    // Plain = Union[Leaf, Group]
    private function item(): null | Leaf | Group | Opt | Repeat | Forced | Lookahead | Rhs | Cut {
        return $this->memoize(
            __METHOD__,
            function (): null | Leaf | Group | Opt | Repeat | Forced | Lookahead | Rhs | Cut {
                # item: '[' ~ alts ']' | atom '?' | atom '*' | atom '+' | atom '.' atom '+' | atom
                $index = $this->index();
                $cut = false;
                if ($this->expect('[') && ($cut = true) && ($alts = $this->alts()) && $this->expect(']')) {
                    return new Opt($alts);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                if (($atom = $this->atom()) && $this->expect('?')) {
                    return new Opt($atom);
                }
                $this->reset($index);
                if (($atom = $this->atom()) && $this->expect('*')) {
                    return new Repeat0($atom);
                }
                $this->reset($index);
                if (($atom = $this->atom()) && $this->expect('+')) {
                    return new Repeat1($atom);
                }
                $this->reset($index);
                if (
                    ($sep = $this->atom())
                    && $this->expect('.')
                    && ($node = $this->atom())
                    && $this->expect('+')) {
                    return new Gather($sep, $node);
                }
                $this->reset($index);
                if ($atom = $this->atom()) {
                    return $atom;
                }
                $this->reset($index);
                return null;
            }
        );
    }

    // def atom(self) -> Optional[Plain]:
    // Plain = Union[Leaf, Group]
    private function atom(): null | Leaf | Group {
        return $this->memoize(
            __METHOD__,
            function (): null | Leaf | Group {
                # atom: '(' ~ alts ')' | NAME | STRING
                $index = $this->index();
                $cut = false;
                if ($this->expect('(') && ($cut = true) && ($alts = $this->alts()) &&$this->expect(')')) {
                    return new Group($alts);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                if ($name = $this->name()) {
                    return new NameLeaf($name->val);
                }
                $this->reset($index);
                if ($string = $this->string()) {
                    return new StringLeaf($string->val);
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function action(): ?string {
        return $this->memoize(
            __METHOD__,
            function (): ?string {
                # action: "{" ~ target_atoms "}"
                $index = $this->index();
                //$cut = false;
                if ($this->expect("{") /*&& ($cut = true)*/ && ($targetAtoms = $this->targetAtoms()) && $this->expect("}")) {
                    return $targetAtoms;
                }
                $this->reset($index);
                /*if ($cut) {
                    return null;
                }*/
                return null;
            }
        );
    }

    private function annotation(): ?string {
        return $this->memoize(
            __METHOD__,
            function (): ?string {
                # annotation: "[" ~ target_atoms "]"
                $index = $this->index();
                if ($this->expect('[') && ($targetAtoms = $this->targetAtoms()) && $this->expect(']')) {
                    return $targetAtoms;
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function targetAtoms(): ?string {
        return $this->memoize(
            __METHOD__,
            function (): ?string {
                # target_atoms: target_atom target_atoms | target_atom
                $index = $this->index();
                if (($targetAtom = $this->targetAtom()) && ($targetAtoms = $this->targetAtoms())) {
                    return $targetAtom . " " . $targetAtoms;
                }
                $this->reset($index);
                if ($targetAtom = $this->targetAtom()) {
                    return $targetAtom;
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function targetAtom(): ?string {
        return $this->memoize(
            __METHOD__,
            function (): ?string {
                # target_atom: "{" ~ target_atoms? "}" | "[" ~ target_atoms? "]" | NAME "*" | NAME | NUMBER | STRING | "?" | ":" | !"}" !"]" OP
                $index = $this->index();
                $cut = false;
                if ($this->expect("{")
                    && ($cut = true)
                    && ($atoms = $this->targetAtoms())
                    && $this->expect("}")) {
                    return "{" . $atoms . "}";
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                //$cut = false;
                if ($this->expect('[') && ($cut = true) && ($atoms = $this->targetAtoms()) && $this->expect(']')) {
                    return '[' . ($atoms ?? '') . ']';
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                if (($name = $this->name()) && $this->expect('*')) {
                    return $name->val . '*';
                }
                $this->reset($index);
                if ($name = $this->name()) {
                    return $name->val;
                }
                $this->reset($index);
                if ($number = $this->number()) {
                    return $number->val;
                }
                $this->reset($index);
                if ($string = $this->string()) {
                    return $string->val;
                }
                $this->reset($index);
                if ($this->expect('?"')) {
                    return '?';
                }
                $this->reset($index);
                if ($this->expect(':')) {
                    return ':';
                }
                $this->reset($index);
                if ($this->negativeLookahead($this->expect(...), '}') && $this->negativeLookahead($this->expect(...), ']') && ($op = $this->op())) {
                    return $op->val;
                }
                $this->reset($index);
                return null;
            }
        );
    }
}

// MetaList = List[MetaTuple]
class MetaList {
}

// RuleList = List[Rule]
class RuleList extends \ArrayObject {
}

class Rule {
}

// RuleName = Tuple[str, str]
class RuleName {

}

// MetaTuple = Tuple[str, Optional[str]]
class MetaTuple {

}

class Rhs {

}

class Alt {

}

class NamedItem {

}

// NamedItemList = List[NamedItem]
class NamedItemList {}

// LookaheadOrCut = Union[Lookahead, Forced, Cut]
class Forced {}
class Cut {}
class Lookahead {
}

class PositiveLookahead extends Lookahead {}
class NegativeLookahead extends Lookahead {}

class Opt {}
class Repeat {}
class Repeat0 extends Repeat {}
class Repeat1 extends Repeat {}
class Gather extends Repeat {}

class Group {}
class Leaf {}

class NameLeaf extends Leaf {}

class StringLeaf extends Leaf {}