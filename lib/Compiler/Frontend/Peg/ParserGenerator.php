<?php declare(strict_types=1);
/**
 * This file is part of njcom/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Base\NotImplementedException;
use Stringable;
use UnexpectedValueException;

use const Morpho\Base\INDENT;

/**
 * Based on https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/parser_generator.py
 */
abstract class ParserGenerator {
    protected Grammar $grammar;

    /**
     * For name_rule()/name_loop()
     */
    protected int $counter = 0;

    protected GrammarVisitor $callMakerVisitor;

    /**
     * @var resource IO[Text]
     */
    protected $stream;

    protected int $level = 0;

    /**
     * @var mixed Dict[str, Rule]
     */
    protected array $allRules;

    /**
     * @var array List[List[str]]
     */
    protected array $localVarStack = [];

    /**
     * @var array Dict[str, int]
     */
    protected array $keywords = [];

    /**
     * @var array Set[str]
     */
    protected array $softKeywords = [];

    /**
     * @var array Set[str]
     */
    private array $tokens;

    private int $keywordCounter = 499;

    /**
     * @var array Dict[str, Rule]
     */
    private array $rules;

    private array $firstGraph;

    private iterable $firstSccs;

    public function __construct(Grammar $grammar, array $tokens, $targetStream) {
        $this->grammar = $grammar;
        $this->tokens = $tokens;
        $this->rules = $grammar->rules;
        $this->validateRuleNames();
        if (!isset($this->grammar->metas['trailer']) && !isset($this->rules['start'])) {
            throw new GrammarException("Grammar without a trailer must have a 'start' rule");
        }
        $checker = new RuleCheckingVisitor($this->rules, $this->tokens);
        foreach ($this->rules as $rule) {
            $checker->visit($rule);
        }
        $this->stream = $targetStream; // self.file in Python
        [$this->firstGraph, $this->firstSccs] = $this->computeLeftRecursives($this->rules);
        // self.all_rules:  = self.rules.copy()
        $this->allRules = $this->rules; # Rules + temporal rules
    }

    /**
     * Differs from the Python's signature: generate(self, filename: str) -> None:
     */
    abstract public function generate(array $context): string;

    public function artificialRuleFromRhs(Rhs $rhs): string {
        throw new NotImplementedException();
        /*self.counter += 1
        name = f"_tmp_{self.counter}"  # TODO: Pick a nicer name.
        self.all_rules[name] = Rule(name, None, rhs)
        return name*/
    }

    public function artificialRuleFromGather(Gather $node): string {
        $this->counter++;
        $name = '_gather_' . $this->counter;
        $this->counter++;
        //extra_function_name = f"_loop0_{self.counter}"
        $extraFunctionName = '_loop0_' . $this->counter;
        $extraFunctionAlt = new Alt(
            [
                new NamedItem(null, $node->separator),
                new NamedItem('elem', $node->node),
            ],
            action: 'elem',
        );
        $this->allRules[$extraFunctionName] = new Rule($extraFunctionName, null, new Rhs([$extraFunctionAlt]));
        $alt = new Alt([
            new NamedItem('elem', $node->node),
            new NamedItem('seq', new NameLeaf($extraFunctionName)),
        ]);
        $this->allRules[$name] = new Rule($name, null, new Rhs([$alt]));
        return $name;
    }

    public function keywordType(): int {
        $this->keywordCounter++;
        return $this->keywordCounter;
    }

    protected function validateRuleNames(): void {
        foreach ($this->rules as $name => $_) {
            if (str_starts_with($name, '_')) {
                throw new GrammarException("Rule names cannot start with underscore: '{$name}'");
            }
        }
    }

    /*
        @contextlib.contextmanager
        def local_variable_context(self) -> Iterator[None]:
            self._local_variable_stack.append([])
            yield
            self._local_variable_stack.pop()

        @property
        def local_variable_names(self) -> List[str]:
            return self._local_variable_stack[-1]
    */

    /*
        @contextlib.contextmanager
        def indent(self) -> Iterator[None]:
            self.level += 1
            try:
                yield
            finally:
                self.level -= 1



        def printblock(self, lines: str) -> None:
            for line in lines.splitlines():
                self.print(line)
    */
    protected function write(Stringable|string $val = null): void {
        if (null === $val) {
            fwrite($this->stream, "\n");
        } else {
            fwrite($this->stream, str_repeat(INDENT, $this->level) . (string)$val . "\n");
        }
    }

    protected function dedupe(string $name): string {
        $origName = $name;
        $counter = 0;
        while (in_array($name, $this->localVarStack[count($this->localVarStack) - 1])) {
            $counter++;
            $name = $origName . '_' . $counter;
        }
        $this->localVarStack[count($this->localVarStack) - 1][] = $name;
        return $name;
    }

    protected function collectRules(): void {
        $keywordCollector = new KeywordCollectorVisitor($this, $this->keywords, $this->softKeywords);
        foreach ($this->allRules as $rule) {
            $keywordCollector->visit($rule);
        }

        $ruleCollector = new RuleCollectorVisitor($this->rules, $this->callMakerVisitor);
        $done = []; // Set[str] = set()
        while (true) {
            $computedRules = $this->allRules;
            $todo = [];
            foreach ($computedRules as $ruleName => $_) {
                if (!isset($done[$ruleName])) {
                    $todo[] = $ruleName;
                }
            }
            if (!$todo) {
                break;
            }
            $done = array_fill_keys(array_keys($this->allRules), true);
            foreach ($todo as $ruleName) {
                $ruleCollector->visit($this->allRules[$ruleName]);
            }
        }
    }

    /**
     * @param array $rules Dict[str, Rule]
     * @return array Tuple[Dict[str, AbstractSet[str]], List[AbstractSet[str]]]
     */
    private function computeLeftRecursives(array $rules): array {
        // Dict[str, AbstractSet[str]]
        $graph = $this->makeFirstGraph($rules);
        //sccs = list(sccutils.strongly_connected_components(graph.keys(), graph))
        $sccs = Scc::stronglyConnectedComponents(array_keys($graph), $graph);
        foreach ($sccs as $scc) {
            /** @var array $scc */
            if (count($scc) > 1) {
                foreach ($scc as $name) {
                    $rules[$name]->leftRecursive = true;
                }
                // Try to find a leader such that all cycles go through it.
                $leaders = array_unique($scc);
                foreach ($scc as $start) {
                    foreach (Scc::findCyclesInScc($graph, $scc, $start) as $cycle) {
                        $leaders = array_diff(
                            $leaders,
                            array_diff(
                                $scc,
                                array_unique($cycle)
                            )
                        );
                        if (!$leaders) {
                            throw new UnexpectedValueException("SCC {$scc} has no leadership candidate (no element is included in all cycles)");
                        }
                    }
                }
                $leader = min($leaders); // Pick an arbitrary leader from the candidates.
                $rules[$leader]->leader = true;
            } else {
                $name = min($scc);
                if (in_array($name, $graph[$name])) {
                    $rules[$name]->leftRecursive = true;
                    $rules[$name]->leader = true;
                }
            }
        }
        return [$graph, $sccs];
    }

    /**
     * Compute the graph of left-invocations.
     * def make_first_graph(rules: Dict[str, Rule]) -> Dict[str, AbstractSet[str]]:
     */
    private function makeFirstGraph(array $rules): array {
        // There's an edge from A to B if A may invoke B at its initial position.
        // Note that this requires the nullable flags to have been computed.
        $initialNameVisitor = new InitialNameVisitor($rules);
        $graph = [];
        $vertices = []; // Set[str] = set()
        foreach ($rules as $name => $rhs) {
            $graph[$name] = $names = $initialNameVisitor->visit($rhs);
            $vertices = array_unique(array_merge($vertices, $names));
        }
        foreach ($vertices as $vertex) {
            if (!isset($graph[$vertex])) {
                $graph[$vertex] = [];
            }
        }
        return $graph;
    }
    /*

        def artifical_rule_from_rhs(self, rhs: Rhs) -> str:

        def artificial_rule_from_repeat(self, node: Plain, is_repeat1: bool) -> str:
            self.counter += 1
            if is_repeat1:
                prefix = "_loop1_"
            else:
                prefix = "_loop0_"
            name = f"{prefix}{self.counter}"
            self.all_rules[name] = Rule(name, None, Rhs([Alt([NamedItem(None, node)])]))
            return name
        */
}
