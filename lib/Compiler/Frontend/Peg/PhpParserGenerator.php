<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Base\NotImplementedException;

use function Morpho\Base\camelize;
use function Morpho\Base\enumVals;
use function Morpho\Base\last;
use function Morpho\Base\tpl;

/**
 * [class PythonParserGenerator(ParserGenerator, GrammarVisitor)](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/python_generator.py#L192)
 */
class PhpParserGenerator extends ParserGenerator implements IGrammarVisitor {
    private InvalidNodeVisitor $invalidVisitor;
    private ?string $unreachableFormatting;
    private ?string $locationFormatting;

    /**
     * __init__(self, grammar: grammar.Grammar, file: Optional[IO[Text]], tokens: Set[str] = set(token.tok_name.values()), location_formatting: Optional[str] = None,unreachable_formatting: Optional[str] = None)
     */
    public function __construct(Grammar $grammar, $stream, $tokens = null, string $locationFormatting = null, string $unreachableFormatting = null) {
        if (null === $tokens) {
            $tokens = array_keys(enumVals(TokenType::class));
            //$tokens[] = TokenType::SOFT_KEYWORD;
            //$tokens = array_unique($tokens);
        }
        parent::__construct($grammar, $tokens, $stream);
        $this->callMakerVisitor = new PhpCallMakerVisitor($this);
        $this->invalidVisitor = new InvalidNodeVisitor();
        $this->unreachableFormatting = $unreachableFormatting ?? "null  // pragma: no cover";
        $this->locationFormatting = ($locationFormatting ?? "lineno=start_lineno, col_offset=start_col_offset, ") . "end_lineno=end_lineno, end_col_offset=end_col_offset";
    }


    public function generate(array $vars = null): void {
        // @todo: Use PhpParser's generator
        $this->collectRules();
        $header = $this->grammar->metas['header'] ?? $this->fileHeader($vars);
        if (null !== $header) {
            $this->print($header);
        }
        $subheader = $this->grammar->metas['subheader'] ?? '';
        if ($subheader) {
            $this->print($subheader);
        }
        $className = $this->grammar->metas['class'] ?? 'GeneratedParser';
        $this->print("# Keywords and soft keywords are listed at the end of the parser definition.");
        $this->print("class {$className} extends Parser {");
        foreach ($this->allRules as $rule) {
            $this->print();
            $this->visit($rule);
        }
        $this->print("}");
        //self.print(f"KEYWORDS = {tuple(self.keywords)}")
        $this->print('const KEYWORDS = todo;');
        $this->print('const SOFT_KEYWORDS = todo;');
        //self.print(f"SOFT_KEYWORDS = {tuple(self.soft_keywords)}")
        $footer = $this->grammar->metas['trailer'] ?? $this->fileFooter($className);
        if (null !== $footer) {
            $this->print(rtrim($footer));
        }
    }

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

    /*

    def alts_uses_locations(self, alts: Sequence[Alt]) -> bool:
        for alt in alts:
            if alt.action and "LOCATIONS" in alt.action:
                return True
            for n in alt.items:
                if isinstance(n.item, Group) and self.alts_uses_locations(n.item.rhs.alts):
                    return True
        return False
    */

    protected function visitRule(Rule $node): void {
        $isLoop = $node->isLoop();
        $isGather = $node->isGather();
        $rhs = $node->flatten();
        if ($node->leftRecursive) {
            if ($node->leader) {
                // @todo: Wrap with memoizeLeftRec();
                $this->print('todo: @memoize_left_rec');
            } else {
                // Non-leader rules in a cycle are not memoized, but they must still be logged.
                // see `def logger()` in Tools/peg_generator/pegen/parser.py
                $this->print('@logger');
            }
        } else {
            $this->print('@memoize');
        }
        $nodeType = $node->type ?? 'mixed';
        $this->print('function ' . $node->name . '(): ?' . $nodeType . "{");
        $this->print('// todo');
        $this->print('}');
        /*


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
        */
    }

    protected function visitNamedItem(NamedItem $node): void {
        [$name, $call] = $this->callMakerVisitor->visit($node->item);
        if ($node->name) {
            $name = $node->name;
        }
        if (!$node->name) {
            $this->print($call);
        } else {
            if ($name != 'cut') {
                $name = $this->dedupe($name);
            }
            // self.print(f"({name} := {call})")
            $this->print($name . ' = ' . $call);
        }
    }

    protected function visitRhs(Rhs $node, bool $isLoop = false, bool $isGather = false): void {
        throw new NotImplementedException();
/*        if is_loop:
            assert len(node.alts) == 1
        for alt in node.alts:
            self.visit(alt, is_loop=is_loop, is_gather=is_gather)*/
    }

    // def visit_Alt(self, node: Alt, is_loop: bool, is_gather: bool) -> None:
    protected function visitAlt(Alt $node, bool $isLoop, bool $isGather): void {
        throw new NotImplementedException();
        /*
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
    }

    private function fileHeader(mixed $vars): ?string {
        return "<?php declare(strict_types=1);\n";
    }

    private function fileFooter(): ?string {
        return '';
    }
}