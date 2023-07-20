<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use function Morpho\Base\camelize;
use function Morpho\Base\enumVals;
use function Morpho\Base\last;

/**
 * [class PythonParserGenerator(ParserGenerator, GrammarVisitor)](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/python_generator.py#L192)
 */
class PhpParserGenerator extends ParserGenerator implements IGrammarVisitor {
/*
    def __init__(
        self,
        grammar: grammar.Grammar,
        file: Optional[IO[Text]],
        tokens: Set[str] = set(token.tok_name.values()),
        location_formatting: Optional[str] = None,
        unreachable_formatting: Optional[str] = None,
    )
 */
    public function __construct(Grammar $grammar, $file = null, $tokens = null, $locationFormatting = null, $unreachableFormatting = null) {
        if (null === $tokens) {
            $tokens = array_keys(enumVals(TokenType::class));
            //$tokens[] = TokenType::SOFT_KEYWORD->value;
        }
        parent::__construct($grammar, $tokens, $file);
        $this->callMakerVisitor = new PhpCallMakerVisitor($this);
/*
        self.callmakervisitor: PythonCallMakerVisitor = PythonCallMakerVisitor(self)
        self.invalidvisitor: InvalidNodeVisitor = InvalidNodeVisitor()
        self.unreachable_formatting = unreachable_formatting or "None  # pragma: no cover"
        self.location_formatting = (
            location_formatting
            or "lineno=start_lineno, col_offset=start_col_offset, "
            "end_lineno=end_lineno, end_col_offset=end_col_offset"
        )
*/
    }

    // def generate(self, filename: str) -> None:
    public function generate(string $string) {
/*
            self.collect_rules()
            header = self.grammar.metas.get("header", MODULE_PREFIX)
            if header is not None:
                basename = os.path.basename(filename)
                self.print(header.rstrip("\n").format(filename=basename))
            subheader = self.grammar.metas.get("subheader", "")
            if subheader:
                self.print(subheader)
            cls_name = self.grammar.metas.get("class", "GeneratedParser")
            self.print("# Keywords and soft keywords are listed at the end of the parser definition.")
            self.print(f"class {cls_name}(Parser):")
            for rule in self.all_rules.values():
                self.print()
                with self.indent():
                    self.visit(rule)

            self.print()
            with self.indent():
                self.print(f"KEYWORDS = {tuple(self.keywords)}")
                self.print(f"SOFT_KEYWORDS = {tuple(self.soft_keywords)}")

            trailer = self.grammar.metas.get("trailer", MODULE_SUFFIX.format(class_name=cls_name))
            if trailer is not None:
                self.print(trailer.rstrip("\n"))*/
    }
    /*

    def alts_uses_locations(self, alts: Sequence[Alt]) -> bool:
        for alt in alts:
            if alt.action and "LOCATIONS" in alt.action:
                return True
            for n in alt.items:
                if isinstance(n.item, Group) and self.alts_uses_locations(n.item.rhs.alts):
                    return True
        return False

    def visit_Rule(self, node: Rule) -> None:
        is_loop = node.is_loop()
        is_gather = node.is_gather()
        rhs = node.flatten()
        if node.left_recursive:
            if node.leader:
                self.print("@memoize_left_rec")
            else:
                # Non-leader rules in a cycle are not memoized,
                # but they must still be logged.
                self.print("@logger")
        else:
            self.print("@memoize")
        node_type = node.type or "Any"
        self.print(f"def {node.name}(self) -> Optional[{node_type}]:")
        with self.indent():
            self.print(f"# {node.name}: {rhs}")
            self.print("mark = self._mark()")
            if self.alts_uses_locations(node.rhs.alts):
                self.print("tok = self._tokenizer.peek()")
                self.print("start_lineno, start_col_offset = tok.start")
            if is_loop:
                self.print("children = []")
            self.visit(rhs, is_loop=is_loop, is_gather=is_gather)
            if is_loop:
                self.print("return children")
            else:
                self.print("return None")

    def visit_NamedItem(self, node: NamedItem) -> None:
        name, call = self.callmakervisitor.visit(node.item)
        if node.name:
            name = node.name
        if not name:
            self.print(call)
        else:
            if name != "cut":
                name = self.dedupe(name)
            self.print(f"({name} := {call})")

    def visit_Rhs(self, node: Rhs, is_loop: bool = False, is_gather: bool = False) -> None:
        if is_loop:
            assert len(node.alts) == 1
        for alt in node.alts:
            self.visit(alt, is_loop=is_loop, is_gather=is_gather)

    def visit_Alt(self, node: Alt, is_loop: bool, is_gather: bool) -> None:
        has_cut = any(isinstance(item.item, Cut) for item in node.items)
        with self.local_variable_context():
            if has_cut:
                self.print("cut = False")
            if is_loop:
                self.print("while (")
            else:
                self.print("if (")
            with self.indent():
                first = True
                for item in node.items:
                    if first:
                        first = False
                    else:
                        self.print("and")
                    self.visit(item)
                    if is_gather:
                        self.print("is not None")

            self.print("):")
            with self.indent():
                action = node.action
                if not action:
                    if is_gather:
                        assert len(self.local_variable_names) == 2
                        action = (
                            f"[{self.local_variable_names[0]}] + {self.local_variable_names[1]}"
                        )
                    else:
                        if self.invalidvisitor.visit(node):
                            action = "UNREACHABLE"
                        elif len(self.local_variable_names) == 1:
                            action = f"{self.local_variable_names[0]}"
                        else:
                            action = f"[{', '.join(self.local_variable_names)}]"
                elif "LOCATIONS" in action:
                    self.print("tok = self._tokenizer.get_last_non_whitespace_token()")
                    self.print("end_lineno, end_col_offset = tok.end")
                    action = action.replace("LOCATIONS", self.location_formatting)

                if is_loop:
                    self.print(f"children.append({action})")
                    self.print(f"mark = self._mark()")
                else:
                    if "UNREACHABLE" in action:
                        action = action.replace("UNREACHABLE", self.unreachable_formatting)
                    self.print(f"return {action}")

            self.print("self._reset(mark)")
            # Skip remaining alternatives if a cut was reached.
            if has_cut:
                self.print("if cut: return None")
 */
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
     * @noinspection PhpMixedReturnTypeCanBeReducedInspection
     */
    protected function genericVisit($node, ...$args): mixed {
        foreach ($node as $value) {
            if (is_array($value)) { // @todo: replace is_array() with is_iterable()?
                foreach ($value as $item) {
                    $this->visit($item, ...$args);
                }
            } else {
                $this->visit($value, ...$args);
            }
        }
        return null;
    }
}