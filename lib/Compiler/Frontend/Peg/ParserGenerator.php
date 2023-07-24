<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

/**
 * Based on https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/parser_generator.py
 */
abstract class ParserGenerator {
    protected Grammar $grammar;

    protected GrammarVisitor $callMakerVisitor;

    /**
     * @var resource Optional[IO[Text]]
     */
    protected $stream;

    /**
     * @var array Set[str]
     */
    private array $tokens;

    /**
     * @var array Dict[str, int]
     */
    private array $keywords = [];

    /**
     * @var array Set[str]
     */
    private array $softKeywords = [];

    private int $level = 0;

    /**
     * For name_rule()/name_loop()
     * @var int
     */
    private int $counter = 0;

    /**
     * For keyword_type()
     * @var int
     */
    private int $keywordCounter = 499;

    /**
     * @var iterable Dict[str, Rule]
     */
    private iterable $rules;

    /**
     * @var mixed Dict[str, Rule]
     */
    private mixed $allRules;

    /**
     * @var array List[List[str]]
     */
    private array $localVarStack = [];

    public function __construct(Grammar $grammar, array $tokens, $stream) {
        $this->grammar = $grammar;
        $this->tokens = $tokens;
        $this->rules = $grammar->rules;
        $this->validateRuleNames();

        /*
                if "trailer" not in grammar.metas and "start" not in self.rules:
                    raise GrammarError("Grammar without a trailer must have a 'start' rule")
                checker = RuleCheckingVisitor(self.rules, self.tokens)
                for rule in self.rules.values():
                    checker.visit(rule)
        */
        $this->stream = $stream; // self.file in Python
        [$this->firstGraph, $this->firstSccs] = $this->computeLeftRecursives($this->rules);

        // self.all_rules:  = self.rules.copy()
        $this->allRules = $this->rules->copy(); # Rules + temporal rules
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
    abstract public function generate(string $filePath): void;

    /*
        @contextlib.contextmanager
        def indent(self) -> Iterator[None]:
            self.level += 1
            try:
                yield
            finally:
                self.level -= 1

        def print(self, *args: object) -> None:
            if not args:
                print(file=self.file)
            else:
                print("    " * self.level, end="", file=self.file)
                print(*args, file=self.file)

        def printblock(self, lines: str) -> None:
            for line in lines.splitlines():
                self.print(line)
    */
    protected function collectRules(): void {
        $keywordCollector = new KeywordCollectorVisitor($this->keywords, $this->softKeywords);
        foreach (d($this->allRules) as $rule => $_) {
            $keywordCollector->visit($rule);
        }

        $ruleCollector = new RuleCollectorVisitor($this->rules, $this->callMakerVisitor);
        // done: Set[str] = set()
        $done = [];
        while (true) {
            # computed_rules = list(self.all_rules)
            $computedRules = (array)$this->allRules;
            /*
                        todo = [i for i in computed_rules if i not in done]
                        if not todo:
                            break
                        done = set(self.all_rules)
                        for rulename in todo:
                            rule_collector.visit(self.all_rules[rulename])
             */
        }
    }

    // def compute_left_recursives(rules: Dict[str, Rule]) -> Tuple[Dict[str, AbstractSet[str]], List[AbstractSet[str]]]:
    private function computeLeftRecursive(array $rules): array {
        $graph = $this->makeFirstGraph($rules);
        //sccs = list(sccutils.strongly_connected_components(graph.keys(), graph))
        $sccs = Scc::stronglyConnectedComponents($graph->keys(), $graph);
/*

        graph = make_first_graph(rules)
        for scc in sccs:
            if len(scc) > 1:
                for name in scc:
                    rules[name].left_recursive = True
                # Try to find a leader such that all cycles go through it.
                leaders = set(scc)
                for start in scc:
                    for cycle in sccutils.find_cycles_in_scc(graph, scc, start):
                        # print("Cycle:", " -> ".join(cycle))
                        leaders -= scc - set(cycle)
                        if not leaders:
                            raise ValueError(
                                f"SCC {scc} has no leadership candidate (no element is included in all cycles)"
                            )
                # print("Leaders:", leaders)
                leader = min(leaders)  # Pick an arbitrary leader from the candidates.
                rules[leader].leader = True
            else:
                name = min(scc)  # The only element.
                if name in graph[name]:
                    rules[name].left_recursive = True
                    rules[name].leader = True
        return graph, sccs
 */
    }
    /*
        def keyword_type(self) -> int:
            self.keyword_counter += 1
            return self.keyword_counter

        def artifical_rule_from_rhs(self, rhs: Rhs) -> str:
            self.counter += 1
            name = f"_tmp_{self.counter}"  # TODO: Pick a nicer name.
            self.all_rules[name] = Rule(name, None, rhs)
            return name

        def artificial_rule_from_repeat(self, node: Plain, is_repeat1: bool) -> str:
            self.counter += 1
            if is_repeat1:
                prefix = "_loop1_"
            else:
                prefix = "_loop0_"
            name = f"{prefix}{self.counter}"
            self.all_rules[name] = Rule(name, None, Rhs([Alt([NamedItem(None, node)])]))
            return name

        def artifical_rule_from_gather(self, node: Gather) -> str:
            self.counter += 1
            name = f"_gather_{self.counter}"
            self.counter += 1
            extra_function_name = f"_loop0_{self.counter}"
            extra_function_alt = Alt(
                [NamedItem(None, node.separator), NamedItem("elem", node.node)],
                action="elem",
            )
            self.all_rules[extra_function_name] = Rule(
                extra_function_name,
                None,
                Rhs([extra_function_alt]),
            )
            alt = Alt(
                [NamedItem("elem", node.node), NamedItem("seq", NameLeaf(extra_function_name))],
            )
            self.all_rules[name] = Rule(
                name,
                None,
                Rhs([alt]),
            )
            return name

        def dedupe(self, name: str) -> str:
            origname = name
            counter = 0
            while name in self.local_variable_names:
                counter += 1
                name = f"{origname}_{counter}"
            self.local_variable_names.append(name)
            return name
     */
    /*
    class RuleCheckingVisitor(GrammarVisitor):
        def __init__(self, rules: Dict[str, Rule], tokens: Set[str]):
            self.rules = rules
            self.tokens = tokens

        def visit_NameLeaf(self, node: NameLeaf) -> None:
            if node.value not in self.rules and node.value not in self.tokens:
                raise GrammarError(f"Dangling reference to rule {node.value!r}")

        def visit_NamedItem(self, node: NamedItem) -> None:
            if node.name and node.name.startswith("_"):
                raise GrammarError(f"Variable names cannot start with underscore: '{node.name}'")
            self.visit(node.item)

    class InitialNamesVisitor(GrammarVisitor):
        def __init__(self, rules: Dict[str, Rule]) -> None:
            self.rules = rules
            self.nullables = compute_nullables(rules)

        def generic_visit(self, node: Iterable[Any], *args: Any, **kwargs: Any) -> Set[Any]:
            names: Set[str] = set()
            for value in node:
                if isinstance(value, list):
                    for item in value:
                        names |= self.visit(item, *args, **kwargs)
                else:
                    names |= self.visit(value, *args, **kwargs)
            return names

        def visit_Alt(self, alt: Alt) -> Set[Any]:
            names: Set[str] = set()
            for item in alt.items:
                names |= self.visit(item)
                if item not in self.nullables:
                    break
            return names

        def visit_Forced(self, force: Forced) -> Set[Any]:
            return set()

        def visit_LookAhead(self, lookahead: Lookahead) -> Set[Any]:
            return set()

        def visit_Cut(self, cut: Cut) -> Set[Any]:
            return set()

        def visit_NameLeaf(self, node: NameLeaf) -> Set[Any]:
            return {node.value}

        def visit_StringLeaf(self, node: StringLeaf) -> Set[Any]:
            return set()

    def make_first_graph(rules: Dict[str, Rule]) -> Dict[str, AbstractSet[str]]:
        """Compute the graph of left-invocations.

        There's an edge from A to B if A may invoke B at its initial
        position.

        Note that this requires the nullable flags to have been computed.
        """
        initial_name_visitor = InitialNamesVisitor(rules)
        graph = {}
        vertices: Set[str] = set()
        for rulename, rhs in rules.items():
            graph[rulename] = names = initial_name_visitor.visit(rhs)
            vertices |= names
        for vertex in vertices:
            graph.setdefault(vertex, set())
        return graph

     */
}