<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Closure;
use Morpho\Base\Must;

use Morpho\Base\NotImplementedException;

use function Morpho\Base\camelize;
use function Morpho\Base\enumVals;
use function Morpho\Base\last;

/**
 * [class PythonParserGenerator(ParserGenerator, GrammarVisitor)](https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/python_generator.py#L192)
 * @todo: unify with PhpParserGenerator
 */
class PythonParserGenerator extends ParserGenerator implements IGrammarVisitor {
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
        $this->callMakerVisitor = new PythonCallMakerVisitor($this);
        $this->invalidVisitor = new InvalidNodeVisitor();
        $this->unreachableFormatting = $unreachableFormatting ?? "None  # pragma: no cover";
        $this->locationFormatting = ($locationFormatting ?? "lineno=start_lineno, col_offset=start_col_offset, ") . "end_lineno=end_lineno, end_col_offset=end_col_offset";
    }

    public function generate(array $context = null): string {
        $context = (array) $context;
        $this->collectRules();
        $header = $this->grammar->metas['header'] ?? $this->fileHeader($context);
        if (null !== $header) {
            $this->print($header);
        }
        $subheader = $this->grammar->metas['subheader'] ?? '';
        if ($subheader) {
            $this->print($subheader);
        }
        $className = $this->grammar->metas['class'] ?? 'GeneratedParser';
        $this->print("# Keywords and soft keywords are listed at the end of the parser definition.");
        $this->print("class $className (Parser):");
        $context['class'] = $className;
        foreach ($this->allRules as $rule) {
            $this->print();
            $this->indent(function () use ($rule) {
                $this->visit($rule);
            });
        }
        $this->print();
        $this->indent(function () {
            $this->print("KEYWORDS = (" . implode(', ', array_keys($this->keywords)) . ')');
            $this->print("SOFT_KEYWORDS = (" . implode(', ', array_keys($this->softKeywords)) . ')');
        });
        $footer = $this->grammar->metas['trailer'] ?? $this->fileFooter($context);
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

    /**
     * @noinspection PhpUnused
     */
    protected function visitRule(Rule $node): void {
        if ($node->leftRecursive) {
            if ($node->leader) {
                $this->print("@memoize_left_rec");
            } else {
                // Non-leader rules in a cycle are not memoized, but they must still be logged.
                $this->print("@logger");
            }
        } else {
            $this->print('@memoize');
        }
        $returnType = $node->type ?? 'Any';
        $this->print('def ' . $node->name . '(self): Optional[' . $returnType . ']:');
        $this->indent(function () use ($node) {
            $rhs = $node->flatten();
            $isLoop = $node->isLoop();
            $isGather = $node->isGather();
            $this->print('# ' . $node->name . ': ' . $rhs);
            $this->print('mark = self._mark()');
            if ($this->altsUseLocations($node->rhs->alts)) {
                $this->print('tok = self._tokenizer.peek()');
                $this->print('start_lineno, start_col_offset = tok.start');
           }
            if ($isLoop) {
                $this->print("children = []");
            }
            $this->visit($rhs, isLoop: $isLoop, isGather: $isGather);

            if ($isLoop) {
                $this->print("return children");
            } else {
                $this->print("return None");
            }
        });
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
            $this->print($call);
        } else {
            if ($name != 'cut') {
                $name = $this->dedupe($name);
            }
            $this->print('(' . $name . ' := ' . $call . ')');
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

    /** @noinspection PhpUnused */
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
            $this->print('cut = False');
        }
        if ($isLoop) {
            $this->print('while (');
        } else {
            $this->print('if (');
        }
        $this->indent(function () use ($isGather, $node) {
            $first = true;
            foreach ($node->items as $item) {
                if ($first) {
                    $first = false;
                } else {
                    $this->print('and');
                }
                $this->visit($item);
                if ($isGather) {
                    $this->print('is not None');
                }
            }
        });
        $this->print('):');
        $this->indent(function () use ($isLoop, $node, $isGather) {
            $action = $node->action;
            if (!$action) {
                if ($isGather) {
                    Must::beTruthy(count(last($this->localVarStack)) == 2);
                    $last = last($this->localVarStack);
                    throw new NotImplementedException();
                    $action = '[[$' . $last[0] . '], ' . '$' . $last[1] .');';
/*                        action = (
                            f"[{self.local_variable_names[0]}] + {self.local_variable_names[1]}"
                        )*/
                } else {
                    if ($this->invalidVisitor->visit($node)) {
                        $action = 'UNREACHABLE';
                    } elseif (count(last($this->localVarStack)) == 1) {
                        $action = last($this->localVarStack)[0];
                    } else {
                        $action = '[{' . implode(', ', last($this->localVarStack)) . '}]';
                    }
                }
            } elseif (str_contains($action, 'LOCATIONS')) {
                $this->print('tok = self._tokenizer.get_last_non_whitespace_token()');
                $this->print('"end_lineno, end_col_offset = tok.end');
                $action = str_replace('LOCATIONS', $this->locationFormatting, $action);
            }
            if ($isLoop) {
                $this->print('children.append(' . $action . ')');
                $this->print('mark = self._mark()');
            } else {
                if (str_contains($action, 'UNREACHABLE')) {
                    $action = str_replace('UNREACHABLE', $this->unreachableFormatting, $action);
                }
                $this->print('return ' . $action);
            }
        });
        $this->print('self.reset(mark)');
        // Skip remaining alternatives if a cut was reached.
        if ($hasCut) {
            $this->print('if (cut) return None');
        }
        array_pop($this->localVarStack);
    }

    private function fileHeader(array $context): ?string {
        return '';//"<?php\nnamespace {$context['namespace']};\nuse " . init(get_class($this), '\\') . "\\Parser;";
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

    private function indent(Closure $fn): void {
        throw new NotImplementedException();
    }
}