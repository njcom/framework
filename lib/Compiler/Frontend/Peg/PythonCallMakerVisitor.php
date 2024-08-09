<?php declare(strict_types=1);
/**
 * This file is part of njcom/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Base\NotImplementedException;
use WeakMap;

/**
 * [PythonCallMakerVisitor](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/python_generator.py#L93)
 * @todo: unify with PhpCallMakerVisitor
 */
class PythonCallMakerVisitor extends GrammarVisitor {
    /**
     * @var WeakMap Dict[Any, Any] = {}
     */
    private WeakMap $cache;

    private PythonParserGenerator $gen;

    public function __construct(PythonParserGenerator $parserGenerator) {
        $this->gen = $parserGenerator;
        $this->cache = new WeakMap();
    }

    /**
     * @return array Tuple[Optional[str], str]:
     * @noinspection PhpUnused
     */
    protected function visitNameLeaf(NameLeaf $node): array {
        $name = $node->val;
        switch (true) {
            case $name === 'SOFT_KEYWORD':
                return ["soft_keyword", $this->softKeyword()];
            case in_array($name, ["NAME", "NUMBER", "STRING", "OP", "TYPE_COMMENT"]):
                $name = strtolower($name);
                return [$name, 'self.' . $name . '()'];
            case in_array($name, ["NEWLINE", "DEDENT", "INDENT", "ENDMARKER", "ASYNC", "AWAIT"]):
                // Avoid using names that can be Python keywords
                return ['_' . strtolower($name), '$this->expect(\'' . $name . '\')'];
            default:
                return [$name, 'self.' . $name . '()'];
        }
    }

    /**
     * @return array Tuple[str, str]
     * @noinspection PhpUnused
     */
    protected function visitStringLeaf(StringLeaf $node): array {
        return ['literal', 'self.expect(' . $node->val . ')'];
    }

    /**
     * @return array Tuple[Optional[str], str]
     * @noinspection PhpUnused
     */
    protected function visitRhs(Rhs $node): array {
        if (isset($this->cache[$node])) {
            return $this->cache[$node];
        }
        throw new NotImplementedException();
        /*if node in self.cache:
            return self.cache[node]
        if len(node.alts) == 1 and len(node.alts[0].items) == 1:
            self.cache[node] = self.visit(node.alts[0].items[0])
        else:
            name = self.gen.artifical_rule_from_rhs(node)
            self.cache[node] = name, f"self.{name}()"
        return self.cache[node]*/
    }

    /**
     * @return array Tuple[Optional[str], str]
     * @noinspection PhpUnused
     */
    protected function visitNamedItem(NamedItem $node): array {
        [$name, $call] = $this->visit($node->item);
        if ($node->name) {
            $name = $node->name;
        }
        return [$name, $call];
    }

    /**
     * @return array Tuple[None, str]
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    protected function visitPositiveLookahead(PositiveLookahead $node): array {
        [$head, $tail] = $this->lookaheadCallHelper($node);
        throw new NotImplementedException();
        //return None, f"self.positive_lookahead({head}, {tail})"
    }

    /**
     * @return array Tuple[None, str]
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    protected function visitNegativeLookahead(NegativeLookahead $node): array {
        /*  head, tail = self.lookahead_call_helper(node)
          return None, f"self.negative_lookahead({head}, {tail})"*/
        throw new NotImplementedException();
    }

    /**
     * @return array Tuple[str, str]
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    protected function visitOpt(Opt $node): array {
        /*        name, call = self.visit(node.node)
                # Note trailing comma (the call may already have one comma
                # at the end, for example when rules have both repeat0 and optional
                # markers, e.g: [rule*])
                if call.endswith(","):
                    return "opt", call
                else:
                    return "opt", f"{call},"*/
        throw new NotImplementedException();
    }

    /**
     * @return array Tuple[str, str]
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    protected function visitRepeat0(Repeat0 $node): array {
        throw new NotImplementedException();
        /*
            if node in self.cache:
                return self.cache[node]
            name = self.gen.artificial_rule_from_repeat(node.node, False)
            self.cache[node] = name, f"self.{name}(),"  # Also a trailing comma!
            return self.cache[node]
    */
    }

    /**
     * @return array Tuple[str, str]
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    protected function visitRepeat1(Repeat1 $node): array {
        throw new NotImplementedException();
        /*
              if node in self.cache:
                  return self.cache[node]
              name = self.gen.artificial_rule_from_repeat(node.node, True)
              self.cache[node] = name, f"self.{name}()"  # But no trailing comma here!
              return self.cache[node]
      */
    }

    /**
     * @return array Tuple[str, str]
     * @noinspection PhpUnused
     */
    protected function visitGather(Gather $node): array {
        if (isset($this->cache[$node])) {
            return $this->cache[$node];
        }
        $name = $this->gen->artificialRuleFromGather($node);
        $this->cache[$node] = [$name, 'self.' . $name . '()']; // No trailing comma here either!
        return $this->cache[$node];
    }

    /**
     * @return array Tuple[Optional[str], str]
     * @noinspection PhpUnused
     */
    protected function visitGroup(Group $node): array {
        return $this->visit($node->rhs);
    }

    /**
     * @return array Tuple[str, str]
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    protected function visitCut(Cut $node): array {
        return ['cut', 'True'];
    }

    /**
     * @return array Tuple[str, str]
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    protected function visitForced(Forced $node): array {
        /*
            if isinstance(node.node, Group):
                _, val = self.visit(node.node.rhs)
                return "forced", f"self.expect_forced({val}, '''({node.node.rhs!s})''')"
            else:
                return (
                    "forced",
                    f"self.expect_forced(self.expect({node.node.value}), {node.node.value!r})",
                )
     */
        throw new NotImplementedException();
    }

    /*
    def lookahead_call_helper(self, node: Lookahead) -> Tuple[str, str]:
        name, call = self.visit(node.node)
        head, tail = call.split("(", 1)
        assert tail[-1] == ")"
        tail = tail[:-1]
        return head, tail
*/
    /**
     * @return array Tuple[str, str]
     */
    private function lookaheadCallHelper(Lookahead $node): array {
        /*
        name, call = self . visit(node . node)
        head, tail = call . split("(", 1)
        assert tail[-1] == ")"
        tail = tail[:-1]
        return head, tail
       */
        throw new NotImplementedException();
    }

    private function softKeyword(): string {
        throw new NotImplementedException();
    }
}