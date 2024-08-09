<?php declare(strict_types=1);
/**
 * This file is part of njcom/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Closure;
use Morpho\Base\Must;
use UnexpectedValueException;

use function Morpho\Base\camelize;
use function Morpho\Base\mkStream;
use function Morpho\Base\enumVals;
use function Morpho\Base\last;
use function Morpho\Base\init;
use function Morpho\Base\prepend;

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
    public function __construct(Grammar $grammar, $targetStream, array $tokens = null, string $locationFormatting = null, string $unreachableFormatting = null) {
        if (null === $tokens) {
            $tokens = array_keys(enumVals(TokenType::class));
            //$tokens[] = TokenType::SOFT_KEYWORD;
            //$tokens = array_unique($tokens);
        }
        parent::__construct($grammar, $tokens, $targetStream);
        $this->callMakerVisitor = new PhpCallMakerVisitor($this);
        $this->invalidVisitor = new InvalidNodeVisitor();
        $this->unreachableFormatting = $unreachableFormatting ?? "null  // pragma: no cover";
        $this->locationFormatting = ($locationFormatting ?? "lineno=start_lineno, col_offset=start_col_offset, ") . "end_lineno=end_lineno, end_col_offset=end_col_offset";
    }

    public function generate(array $context = null): string {
        $context = (array)$context;
        if (!isset($context['namespace'])) {
            $context['namespace'] = __NAMESPACE__ . '\\Generated' . str_replace([' ', '.'], '', microtime());
        }
        // @todo: Use PhpParser's generator?
        $this->collectRules();
        $header = $this->grammar->metas['header'] ?? $this->fileHeader($context);
        if (null !== $header) {
            $this->write($header);
        }
        $subheader = $this->grammar->metas['subheader'] ?? '';
        if ($subheader) {
            $this->write($subheader);
        }
        $className = $this->grammar->metas['class'] ?? 'GeneratedParser';
        $context['class'] = $className;
        $this->write("// Keywords and soft keywords are listed at the end of the parser definition.");
        $this->write("class $className extends Parser {");
        foreach ($this->allRules as $rule) {
            $this->write();
            $this->visit($rule);
        }
        $this->write("}");
        //self.print(f"KEYWORDS = {tuple(self.keywords)}")
        $this->write('const KEYWORDS = [' . implode(', ', array_keys($this->keywords)) . '];');
        $this->write('const SOFT_KEYWORDS = [' . implode(', ', array_keys($this->softKeywords)) . '];');
        //self.print(f"SOFT_KEYWORDS = {tuple(self.soft_keywords)}")
        $footer = $this->grammar->metas['trailer'] ?? $this->fileFooter($context);
        if (null !== $footer) {
            $this->write(rtrim($footer));
        }
        return $context['namespace'] . '\\' . $className;
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

    /**
     * @noinspection PhpUnused
     */
    protected function visitRule(Rule $node): void {
        $codeGen = function () use ($node) {
            $isLoop = $node->isLoop();
            $isGather = $node->isGather();
            $rhs = $node->flatten();
            $returnType = $node->type ?? 'mixed';
            if ($returnType !== 'mixed') { // `mixed` already contains `null` type.
                $returnType = $returnType . ' | null';
            }

            $this->write('function ' . $node->name . '(): ' . $returnType . ' {');
            $this->write('// ' . $node->name . ': ' . $rhs);
            $this->write('$index = $this->tokenizer->index();');
            if ($this->altsUseLocations($node->rhs->alts)) {
                $this->write('$tok = $this->tokenizer->peek();');
                $this->write('[$startLineNo, $startColOffset] = $tok->start;');
            }
            if ($isLoop) {
                $this->write('$children = [];');
            }
            $this->visit($rhs, isLoop: $isLoop, isGather: $isGather);

            if ($isLoop) {
                $this->write('return $children;');
            } else {
                $this->write('return null;');
            }

            $this->write('}');
        };

        if ($node->leftRecursive) {
            if ($node->leader) {
                $this->wrap($codeGen, 'return $this->memoizeLeftRec(', ');');
            } else {
                // Non-leader rules in a cycle are not memoized, but they must still be logged.
                // see `def logger()` in Tools/peg_generator/pegen/parser.py
                $this->wrap($codeGen, 'return $this->logger(', ');');
            }
        } else {
            $this->wrap($codeGen, 'return $this->memoize(__METHOD__, ', ');');
        }
    }

    /**
     * @noinspection PhpUnused
     */
    protected function visitNamedItem(NamedItem $node): void {
        [$name, $call] = $this->callMakerVisitor->visit($node->item);
        if ($node->name) {
            $name = $node->name;
        }
        if (!$name) {
            $this->write($call);
        } else {
            if ($name != 'cut') {
                $name = $this->dedupe($name);
            }
            // self.print(f"({name} := {call})")
            $this->write('($' . $name . ' = ' . $call . ')');
        }
    }

    /**
     * @noinspection PhpUnused
     */
    protected function visitRhs(Rhs $node, bool $isLoop = false, bool $isGather = false): void {
        if ($isLoop) {
            Must::beTruthy(count($node->alts) == 1);
        }
        foreach ($node->alts as $alt) {
            $this->visit($alt, isLoop: $isLoop, isGather: $isGather);
        }
    }

    /**
     * @noinspection PhpUnused
     */
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
            $this->write('$cut = false;');
        }
        if ($isLoop) {
            $this->write('while (');
        } else {
            $this->write('if (');
        }
        $first = true;
        foreach ($node->items as $item) {
            if ($first) {
                $first = false;
            } else {
                $this->write('&&');
            }
            $this->visit($item);
            if ($isGather) {
                $this->write('!== null');
            }
        }
        $this->write(') {');
        $action = $node->action;
        if (!$action) {
            if ($isGather) {
                Must::beTruthy(count(last($this->localVarStack)) == 2);
                $last = last($this->localVarStack);
                $action = 'array_merge([$' . $last[0] . '], ' . '$' . $last[1] . ');';
                //$action = f"[{self.local_variable_names[0]}] + {self.local_variable_names[1]}"
            } else {
                if ($this->invalidVisitor->visit($node)) {
                    $action = 'UNREACHABLE';
                } elseif (count(last($this->localVarStack)) == 1) {
                    $action = '$' . last($this->localVarStack)[0];
                } else {
                    $action = '[' . implode(', ', prepend(prefix: '$', list: last($this->localVarStack))) . ']';
                }
            }
        } elseif (str_contains($action, 'LOCATIONS')) {
            $this->write('$tok = $this->tokenizer->lastNonWhitespaceToken();');
            $this->write('[$endLineNo, $endColOffset] = $tok->end');
            $action = str_replace('LOCATIONS', $this->locationFormatting, $action);
        }
        if ($isLoop) {
            $this->write('$children[] = $' . $action . ';');
            $this->write('$index = $this->tokenizer->index();');
        } else {
            if (str_contains($action, 'UNREACHABLE')) {
                $action = str_replace('UNREACHABLE', $this->unreachableFormatting, $action);
            }
            $this->write('return ' . $action . ';');
        }
        $this->write('}');
        $this->write('$this->tokenizer->reset($index);');
        // Skip remaining alternatives if a cut was reached.
        if ($hasCut) {
            $this->write('if ($cut) return null;');
        }
        array_pop($this->localVarStack);
    }

    private function fileHeader(array $context): ?string {
        return "<?php\nnamespace {$context['namespace']};\nuse " . init(get_class($this), '\\') . "\\Parser;";
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    private function fileFooter(array $context): ?string {
        return '';
    }

    /**
     * @param iterable $alts Sequence[Alt]
     */
    private function altsUseLocations(iterable $alts): bool {
        foreach ($alts as $alt) {
            if ($alt->action && str_contains($alt->action, 'LOCATIONS')) {
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

    /**
     * @noinspection PhpSameParameterValueInspection
     */
    private function wrap(Closure $wrappedCodeGen, string $wrapperPreCode, string $wrapperPostCode): void {
        $stream = $this->stream;
        $this->stream = mkStream();

        $wrappedCodeGen();

        $code = stream_get_contents($this->stream, offset: 0);
        $this->stream = $stream;

        $lines = explode("\n", $code);
        $n = count($lines);
        $i = 0;
        $j = $n - 1;
        $leftOffset = $rightOffset = -1;
        // Find non empty start and end lines.
        while ($i < $j) {
            if ($leftOffset < 0) {
                if (trim($lines[$i]) != '') {
                    $leftOffset = $i;
                }
            }
            if ($rightOffset < 0) {
                if (trim($lines[$j]) != '') {
                    $rightOffset = $j;
                }
            }
            if ($leftOffset >= 0 && $rightOffset >= 0) {
                break;
            }
            $i++;
            $j--;
        }
        if ($leftOffset < 0 || $rightOffset < 0) {
            throw new UnexpectedValueException();
        }
        $this->write(
            $lines[$leftOffset] . "\n" // function start like 'public function foo() {'
            . $wrapperPreCode . "\n"   // decorator start like 'return $this->memoize('
            . "function () {\n" . implode("\n", array_slice($lines, $leftOffset + 1, $rightOffset - 1)) . "\n}\n" // function body
            . $wrapperPostCode . "\n" // decorator end like ');'
            . $lines[$rightOffset] // function end like '}'
        );
    }
}