<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Base\NotImplementedException;
use Morpho\Base\Must;
use function Morpho\Base\camelize;
use function Morpho\Base\enumVals;
use function Morpho\Base\last;
use const Morpho\Base\INDENT;

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

    public function generate(string $filePath): string {
        // @todo: Use PhpParser's generator
        $this->collectRules();
        $header = $this->grammar->metas['header'] ?? $this->fileHeader($filePath);
        if (null !== $header) {
            $this->print($header);
        }
        $subheader = $this->grammar->metas['subheader'] ?? '';
        if ($subheader) {
            $this->print($subheader);
        }
        $className = $this->grammar->metas['class'] ?? 'GeneratedParser';
        $this->print("// Keywords and soft keywords are listed at the end of the parser definition.");
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
        return $className;
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

    protected function visitRule(Rule $node): void {
        $isLoop = $node->isLoop();
        $isGather = $node->isGather();
        $rhs = $node->flatten();
        $nodeType = $node->type ?? 'mixed';

        // @todo: save current offset of stream, collect print(), then restore offset

        $this->print('function ' . $node->name . '(): ?' . $nodeType . '{');
        $this->print('// ' . $node->name . ': ' . $rhs);
        $this->print('$mark = $this->mark();');
        if ($this->altsUseLocations($node->rhs->alts)) {
            $this->print('$tok = $this->tokenizer->peek();');
            $this->print('[$startLineNo, $startColOffset] = $tok->start;');
        }
        if ($isLoop) {
            $this->print('$children = [];');
        }
        // @todo: test how unpacking works in PHP
        $this->visit($rhs, isLoop: $isLoop, isGather: $isGather);
        
        if ($isLoop) {
            $this->print('return $children');
        } else {
            $this->print('return null');
        }

        $this->print('}');

        if ($node->leftRecursive) {
            if ($node->leader) {
                // @todo: Wrap with memoizeLeftRec();
                throw new NotImplementedException('Wrap with @memoize_left_rec');
            } else {
                // Non-leader rules in a cycle are not memoized, but they must still be logged.
                // see `def logger()` in Tools/peg_generator/pegen/parser.py
                throw new NotImplementedException('Wrap with @logger');
            }
        } else {
            throw new NotImplementedException('Wrap with @memoize');
        }
        //str_repeat(INDENT, $this->level) 
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
        if ($isLoop) {
            Must::beTruthy(count($node->alts) == 1);
        }
        foreach ($node->alts as $alt) {
            $this->visit($alt, isLoop: $isLoop, isGather: $isGather);
        }
    }

    protected function visitAlt(Alt $node, bool $isLoop, bool $isGather): void {
        $hasCut = false;
        foreach ($node->items as $item) {
            if ($item->item instanceof Cut) {
                $hasCut = true;
                break;
            }
        }
        $this->localVarStack[] = [];
        if ($hasCut) {
            $this->print('$cut = false;');
        }
        if ($isLoop) {
            $this->print('while (');
        } else {
            $this->print('if (');
        }
        $first = true;
        foreach ($node->items as $item) {
            if ($first) {
                $first = false;
            } else {
                $this->print('&&');
            }
            $this->visit($item);
            if ($isGather) {
                $this->print('!== null');
            }
        }
        $this->print(') {');
        $action = $node->action;
        if (!$action) {
            if ($isGather) {
                Must::beTruthy(count(last($this->localVarStack)) == 2);
                throw new NotImplementedException();
                //$last = last($this->localVarStack);
                //$action = '[' . $last[0] . ']' ???
                //$action = f"[{self.local_variable_names[0]}] + {self.local_variable_names[1]}"
            } else {
                if ($this->invalidVisitor->visit($node)) {
                    $action = 'UNREACHABLE';
                } elseif (count(last($this->localVarStack)) == 1) {
                    $action = last($this->localVarStack)[0];
                } else {
                    $action = '[' . implode(', ', last($this->localVarStack)) . ']';
                }
            }
        } elseif (str_contains($action, 'LOCATIONS')) {
            $this->print('$tok = $this->tokenizer->lastNonWhitespaceToken();');
            $this->print('[$endLineNo, $endColOffset] = $tok->end');
            $action = str_replace('LOCATIONS', $this->locationFormatting, $action);
        }
        if ($isLoop) {
            $this->print('$children[] = ' . $action . ';');
            $this->print('$mark = $this->mark();');
        } else {
            if (str_contains($action, 'UNREACHABLE')) {
                $action = str_replace('UNREACHABLE', $this->unreachableFormatting, $action);
            }
            $this->print('return ' . $action . ';');
        }
        $this->print('}');
        $this->print('$this->reset($mark);');
        // Skip remaining alternatives if a cut was reached.
        if ($hasCut) {
            $this->print('if ($cut) return null;');
        }
        array_pop($this->localVarStack);
    }

    private function fileHeader(string $filePath): ?string {
        return "<?php declare(strict_types=1);\n";
    }

    private function fileFooter(): ?string {
        return '';
    }

    /**
     * @param iterable $alts Sequence[Alt]
     */
    private function altsUseLocations(iterable $alts): bool {
        foreach ($alts as $alt) {
            if ($alt->action && in_array('LOCATIONS', $alt->action)) {
                return true;
            }
            foreach ($alt->items as $item) {
                if ($item->item instanceof Group && $this->altsUseLocations($item->iteim->rhs->alts)) {
                    return true;
                }
            }
        }
        return false;
    }
}