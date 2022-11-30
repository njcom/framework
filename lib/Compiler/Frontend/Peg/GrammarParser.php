<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

/*
     Alt,
    Cut,
    Forced,
    Gather,
    Group,
    Item,
    Lookahead,
    LookaheadOrCut,
    MetaTuple,
    MetaList,
    NameLeaf,
    NamedItem,
    NamedItemList,
    NegativeLookahead,
    Opt,
    Plain,
    PositiveLookahead,
    Repeat0,
    Repeat1,
    Rhs,
    Rule,
    RuleList,
    RuleName,
    Grammar,
    StringLeaf,
 */

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
                $index = $this->mark();
                //$cut = false;
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
                $index = $this->mark();
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
                $mark = $this->mark();
                if ($meta = $this->meta() && $metas = $this->metas()) {
                    return MetaList(array_merge([$meta], $metas));
                }
                $this->reset($mark);
                if ($meta = $this->meta()) {
                    return MetaList([$meta]);
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
                $mark = $this->mark();
                if ($literal = $this->expect('@') && $name = $this->name() && $newline = $this->expect('NEWLINE')) {
                    return new MetaTuple($name->string, null);
                }
                $this->reset($mark);
                if ($literal = $this->expect('@') && $a = $this->name() && $b = $this->name() && $newline = $this->expect('NEWLINE')) {
                    return new MetaTuple($a->string, $b->string);
                }
                $this->reset($mark);
                if ($literal = $this->expect('@') && $name = $this->name() && $string = $this->string() && $newline = $this->expect('NEWLINE')) {
                    return new MetaTuple($name->string, literalEval($string->string));
                }
                $this->reset($mark);
                return null;
            }
        );
    }

    private function rules(): ?RuleList {
        return $this->memoize(
            __METHOD__,
            function (): ?RuleList {
                # rules: rule rules | rule
                $mark = $this->mark();
                $cut = false;
                if (
                    ($rule = $this->rule())
                    &&
                    ($rules = $this->rules())
                ) {
                    return new RuleList(array_merge([$rule], $rules));
                }
                $this->reset($mark);
                if ($rule = $this->rule()) {
                    return new RuleList($rule);
                }
                return null;
            }
        );
    }

    private function rule(): ?Rule {
        return $this->memoize(
            __METHOD__,
            function (): ?Rule {
                # rule: rulename memoflag? ":" alts NEWLINE INDENT more_alts DEDENT | rulename memoflag? ":" NEWLINE INDENT more_alts DEDENT | rulename memoflag? ":" alts NEWLINE
                $mark = $this->mark();
                $cut = false;
                if (
                    ($rulename = $this->rulename())
                    &&
                    ($opt = $this->memoflag())
                    &&
                    ($literal = $this->expect(":"))
                    &&
                    ($alts = $this->alts())
                    &&
                    ($newline = $this->expect('NEWLINE'))
                    &&
                    ($indent = $this->expect('INDENT'))
                    &&
                    ($more_alts = $this->more_alts())
                    &&
                    ($dedent = $this->expect('DEDENT'))
                ) {
                    return Rule(rulename [0], rulename [1], Rhs($alts->alts + $more_alts->alts), memo: $opt);
                }

                $this->reset($mark);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                    ($rulename = $this->rulename())
                    &&
                    ($opt = $this->memoflag())
                    &&
                    ($literal = $this->expect(":"))
                    &&
                    ($newline = $this->expect('NEWLINE'))
                    &&
                    ($indent = $this->expect('INDENT'))
                    &&
                    ($more_alts = $this->more_alts())
                    &&
                    ($dedent = $this->expect('DEDENT'))
                ) {
                    return Rule(rulename [0], rulename [1], more_alts, memo: $opt);
                }

                $this->reset($mark);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                    ($rulename = $this->rulename())
                    &&
                    ($opt = $this->memoflag())
                    &&
                    ($literal = $this->expect(":"))
                    &&
                    ($alts = $this->alts())
                    &&
                    ($newline = $this->expect('NEWLINE'))
                ) {
                    return Rule(rulename [0], rulename [1], alts, memo: $opt);
                }
                $this->reset($mark);
                if ($cut) {
                    return null;
                }
                return null;
            }
        );
    }

    private function rulename() {
        return $this->memoize(
            __METHOD__,
            function (): ?RuleName {
                # rulename: NAME '[' NAME '*' ']' | NAME '[' NAME ']' | NAME
                $index = $this->mark();
                $cut = false;
                if (
                    ($name = $this->name())
                    &&
                    ($literal = $this->expect('['))
                    &&
                    ($type = $this->name())
                    &&
                    ($literal_1 = $this->expect('*'))
                    &&
                    ($literal_2 = $this->expect(']'))
                ) {
                    return [$name->string, $type->string + "*"];
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                    ($name = $this->name())
                    &&
                    ($literal = $this->expect('['))
                    &&
                    ($type = $this->name())
                    &&
                    ($literal_1 = $this->expect(']'))
                ) {
                    return [$name->string, $type->string];
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if ($name = $this->name()) {
                    return [$name->string, null];
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function memoflag() {
        return $this->memoize(
            __METHOD__,
            function (): ?string {
                # memoflag: '(' 'memo' ')'
                $index = $this->mark();
                $cut = false;
                if (
                    ($literal = $this->expect('('))
                    &&
                    ($literal_1 = $this->expect('memo'))
                    &&
                    ($literal_2 = $this->expect(')'))
                ) {
                    return "memo";
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                return null;
            }
        );
    }

    private function alts() {
        return $this->memoize(
            __METHOD__,
            function (): ?Rhs {
                # alts: alt "|" alts | alt
                $index = $this->mark();
                $cut = false;
                if (
                    ($alt = $this->alt())
                    &&
                    ($literal = $this->expect("|"))
                    &&
                    ($alts = $this->alts())
                ) {
                    return Rhs(array_merge([$alt], $alts->alts));
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if ($alt = $this->alt()) {
                    return Rhs([$alt]);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                return null;
            }
        );
    }

    private function moreAlts() {
        return $this->memoize(
            __METHOD__,
            function (): ?Rhs {
                # more_alts: "|" alts NEWLINE more_alts | "|" alts NEWLINE
                $index = $this->mark();
                $cut = false;
                if (
                    ($literal = $this->expect("|"))
                    &&
                    ($alts = $this->alts())
                    &&
                    ($newline = $this->expect('NEWLINE'))
                    &&
                    ($more_alts = $this->more_alts())
                ) {
                    return Rhs(array_merge($alts->alts, $more_alts->alts));
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                    ($literal = $this->expect("|"))
                    &&
                    ($alts = $this->alts())
                    &&
                    ($newline = $this->expect('NEWLINE'))
                ) {
                    return Rhs($alts->alts);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                return null;
            }
        );
    }

    private function alt() {
        return $this->memoize(
            __METHOD__,
            function (): ?Alt {
                # alt: items '$' action | items '$' | items action | items

                $index = $this->mark();
                $cut = false;
                if (
                    ($items = $this->items())
                    &&
                    ($literal = $this->expect('$'))
                    &&
                    ($action = $this->action())
                ) {
                    return Alt($items + [NamedItem(null, NameLeaf('ENDMARKER'))], action: $action);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                    ($items = $this->items())
                    &&
                    ($literal = $this->expect('$'))
                ) {
                    return Alt($items + [NamedItem(null, NameLeaf('ENDMARKER'))], action: null);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                    ($items = $this->items())
                    &&
                    ($action = $this->action())
                ) {
                    return Alt($items, action: $action);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                ($items = $this->items())
                ) {
                    return Alt($items, action: null);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                return null;
            }
        );
    }

    private function items() {
        return $this->memoize(
            __METHOD__,
            function (): ?NamedItemList {
                # items: named_item items | named_item

                $index = $this->mark();
                $cut = false;
                if (
                    ($named_item = $this->named_item())
                    &&
                    ($items = $this->items())
                ) {
                    return array_merge([$named_item], $items);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                ($named_item = $this->named_item())
                ) {
                    return [named_item];
                }
                $this->reset($index);
                return null;
            }
        );
    }

    function namedItem() {
        return $this->memoize(
            __METHOD__,
            function (): ?NamedItem {
                # named_item: NAME '[' NAME '*' ']' '=' ~ item | NAME '[' NAME ']' '=' ~ item | NAME '=' ~ item | item | forced_atom | lookahead
                $index = $this->mark();
                $cut = false;
                if (
                    ($name = $this->name())
                    &&
                    ($literal = $this->expect('['))
                    &&
                    ($type = $this->name())
                    &&
                    ($literal_1 = $this->expect('*'))
                    &&
                    ($literal_2 = $this->expect(']'))
                    &&
                    ($literal_3 = $this->expect('='))
                    &&
                    ($cut = true)
                    &&
                    ($item = $this->item())
                ) {
                    return NamedItem($name->string, item, $type->string/*f"{type.string}*"*/);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                    ($name = $this->name())
                    &&
                    ($literal = $this->expect('['))
                    &&
                    ($type = $this->name())
                    &&
                    ($literal_1 = $this->expect(']'))
                    &&
                    ($literal_2 = $this->expect('='))
                    &&
                    ($cut = true)
                    &&
                    ($item = $this->item())
                ) {
                    return NamedItem($name->string, $item, $type->string);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                    ($name = $this->name())
                    &&
                    ($literal = $this->expect('='))
                    &&
                    ($cut = true)
                    &&
                    ($item = $this->item())
                ) {
                    return NamedItem($name->string, $item);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                ($item = $this->item())
                ) {
                    return NamedItem(null, $item);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                ($it = $this->forced_atom())
                ) {
                    return NamedItem(null, $it);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                ($it = $this->lookahead())
                ) {
                    return NamedItem(null, $it);
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function forcedAtom() {
        return $this->memoize(
            __METHOD__,
            function (): ?NamedItem {
                # forced_atom: '&' '&' ~ atom
                $index = $this->mark();
                $cut = false;
                if (
                    ($literal = $this->expect('&'))
                    &&
                    ($literal_1 = $this->expect('&'))
                    &&
                    ($cut = true)
                    &&
                    ($atom = $this->atom())
                ) {
                    return Forced($atom);
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function lookahead() {
        return $this->memoize(
            __METHOD__,
            function (): ?LookaheadOrCut {
                # lookahead: '&' ~ atom | '!' ~ atom | '~'
                $index = $this->mark();
                $cut = false;
                if (
                    ($literal = $this->expect('&'))
                    &&
                    ($cut = true)
                    &&
                    ($atom = $this->atom())
                ) {
                    return PositiveLookahead($atom);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                    ($literal = $this->expect('!'))
                    &&
                    ($cut = true)
                    &&
                    ($atom = $this->atom())
                ) {
                    return NegativeLookahead($atom);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if ($literal = $this->expect('~')) {
                    return Cut();
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function item() {
        return $this->memoize(
            __METHOD__,
            function (): ?Item {
                # item: '[' ~ alts ']' | atom '?' | atom '*' | atom '+' | atom '.' atom '+' | atom
                $index = $this->mark();
                $cut = false;
                if (
                    ($literal = $this->expect('['))
                    &&
                    ($cut = true)
                    &&
                    ($alts = $this->alts())
                    &&
                    ($literal_1 = $this->expect(']'))
                ) {
                    return Opt($alts);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                    ($atom = $this->atom())
                    &&
                    ($literal = $this->expect('?'))
                ) {
                    return Opt($atom);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                    ($atom = $this->atom())
                    &&
                    ($literal = $this->expect('*'))
                ) {
                    return Repeat0($atom);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                    ($atom = $this->atom())
                    &&
                    ($literal = $this->expect('+'))
                ) {
                    return Repeat1($atom);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if (
                    ($sep = $this->atom())
                    &&
                    ($literal = $this->expect('.'))
                    &&
                    ($node = $this->atom())
                    &&
                    ($literal_1 = $this->expect('+'))
                ) {
                    return Gather($sep, node);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if ($atom = $this->atom()) {
                    return $atom;
                }
                $this->reset($index);
                return null;
            }
        );
    }

    private function atom() {
        return $this->memoize(
            __METHOD__,
            function (): ?Plain {
                # atom: '(' ~ alts ')' | NAME | STRING

                $index = $this->mark();
                $cut = false;
                if (
                    ($literal = $this->expect('('))
                    &&
                    ($cut = true)
                    &&
                    ($alts = $this->alts())
                    &&
                    ($literal_1 = $this->expect(')'))
                ) {
                    return Group($alts);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if ($name = $this->name()) {
                    return NameLeaf($name->string);
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if ($string = $this->string()) {
                    return StringLeaf($string->string);
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
                # action: "{" ~ targetAtoms "}"
                $index = $this->mark();
                if (($literal = $this->expect("{")) && ($cut = true) && ($targetAtoms = $this->targetAtoms(
                    )) && ($literal_1 = $this->expect("}"))) {
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
                # targetAtoms: targetAtom targetAtoms | targetAtom
                $index = $this->mark();
                $cut = false;
                if (
                    ($targetAtom = $this->targetAtom())
                    &&
                    ($targetAtoms = $this->targetAtoms())
                ) {
                    return $targetAtom . " " . $targetAtoms;
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if ($targetAtom = $this->targetAtom()) {
                    return $targetAtom;
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                return null;
            }
        );
    }

    private function targetAtom(): ?string {
        return $this->memoize(
            __METHOD__,
            function (): ?string {
                # targetAtom: "{" ~ targetAtoms "}" | NAME | NUMBER | STRING | "?" | ":" | !"}" OP
                $index = $this->mark();
                $cut = false;
                if (($literal = $this->expect("{"))
                    &&
                    ($cut = true)
                    &&
                    ($targetAtoms = $this->targetAtoms())
                    &&
                    ($literal_1 = $this->expect("}"))
                ) {
                    return "{" . $targetAtoms . "}";
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if ($name = $this->name()) {
                    return $name->string;
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if ($number = $this->number()) {
                    return $number->string;
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if ($string = $this->string()) {
                    return $string->string;
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if ($literal = $this->expect("?")) {
                    return "?";
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                if ($literal = $this->expect(":")) {
                    return ":";
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                $cut = false;
                // todo
                if ($this->negative_lookahead($this->expect, "}") && ($op = $this->op())) {
                    return $op->string;
                }
                $this->reset($index);
                if ($cut) {
                    return null;
                }
                return null;
            }
        );
    }

    private function literalEval($string) {
        // todo:
        // import ast.literal_eval
        return $string;
    }
}