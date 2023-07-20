<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Base\NotImplementedException;

/**
 * https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/python_generator.py#L93
 */
class PhpCallMakerVisitor extends GrammarVisitor {
    // self.cache: Dict[Any, Any] = {}
    private array $cache = [];
    private PhpParserGenerator $gen;

    public function __construct(PhpParserGenerator $parserGenerator ) {
        $this->gen = $parserGenerator;
    }

    // def visit_NameLeaf(self, node: NameLeaf) -> Tuple[Optional[str], str]:
    protected function visitNameLeaf(NameLeaf $node): array {
        /*
                name = node.value
                if name == "SOFT_KEYWORD":
                    return "soft_keyword", "self.soft_keyword()"
                if name in ("NAME", "NUMBER", "STRING", "OP", "TYPE_COMMENT"):
                    name = name.lower()
                    return name, f"self.{name}()"
                if name in ("NEWLINE", "DEDENT", "INDENT", "ENDMARKER", "ASYNC", "AWAIT"):
                    # Avoid using names that can be Python keywords
                    return "_" + name.lower(), f"self.expect({name!r})"
                return name, f"self.{name}()"
        */
        throw new NotImplementedException();
    }
    
/*
    def visit_StringLeaf(self, node: StringLeaf) -> Tuple[str, str]:
        return "literal", f"self.expect({node.value})"

    def visit_Rhs(self, node: Rhs) -> Tuple[Optional[str], str]:
        if node in self.cache:
            return self.cache[node]
        if len(node.alts) == 1 and len(node.alts[0].items) == 1:
            self.cache[node] = self.visit(node.alts[0].items[0])
        else:
            name = self.gen.artifical_rule_from_rhs(node)
            self.cache[node] = name, f"self.{name}()"
        return self.cache[node]

    def visit_NamedItem(self, node: NamedItem) -> Tuple[Optional[str], str]:
        name, call = self.visit(node.item)
        if node.name:
            name = node.name
        return name, call

    def lookahead_call_helper(self, node: Lookahead) -> Tuple[str, str]:
        name, call = self.visit(node.node)
        head, tail = call.split("(", 1)
        assert tail[-1] == ")"
        tail = tail[:-1]
        return head, tail

    def visit_PositiveLookahead(self, node: PositiveLookahead) -> Tuple[None, str]:
        head, tail = self.lookahead_call_helper(node)
        return None, f"self.positive_lookahead({head}, {tail})"

    def visit_NegativeLookahead(self, node: NegativeLookahead) -> Tuple[None, str]:
        head, tail = self.lookahead_call_helper(node)
        return None, f"self.negative_lookahead({head}, {tail})"

    def visit_Opt(self, node: Opt) -> Tuple[str, str]:
        name, call = self.visit(node.node)
        # Note trailing comma (the call may already have one comma
        # at the end, for example when rules have both repeat0 and optional
        # markers, e.g: [rule*])
        if call.endswith(","):
            return "opt", call
        else:
            return "opt", f"{call},"

    def visit_Repeat0(self, node: Repeat0) -> Tuple[str, str]:
        if node in self.cache:
            return self.cache[node]
        name = self.gen.artificial_rule_from_repeat(node.node, False)
        self.cache[node] = name, f"self.{name}(),"  # Also a trailing comma!
        return self.cache[node]

    def visit_Repeat1(self, node: Repeat1) -> Tuple[str, str]:
        if node in self.cache:
            return self.cache[node]
        name = self.gen.artificial_rule_from_repeat(node.node, True)
        self.cache[node] = name, f"self.{name}()"  # But no trailing comma here!
        return self.cache[node]

    def visit_Gather(self, node: Gather) -> Tuple[str, str]:
        if node in self.cache:
            return self.cache[node]
        name = self.gen.artifical_rule_from_gather(node)
        self.cache[node] = name, f"self.{name}()"  # No trailing comma here either!
        return self.cache[node]

    def visit_Group(self, node: Group) -> Tuple[Optional[str], str]:
        return self.visit(node.rhs)

    def visit_Cut(self, node: Cut) -> Tuple[str, str]:
        return "cut", "True"

    def visit_Forced(self, node: Forced) -> Tuple[str, str]:
        if isinstance(node.node, Group):
            _, val = self.visit(node.node.rhs)
            return "forced", f"self.expect_forced({val}, '''({node.node.rhs!s})''')"
        else:
            return (
                "forced",
                f"self.expect_forced(self.expect({node.node.value}), {node.node.value!r})",
            )
 */
}