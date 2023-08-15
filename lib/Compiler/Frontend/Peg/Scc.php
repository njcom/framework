<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Generator;
use Morpho\Base\NotImplementedException;

/**
 * https://github.com/python/cpython/blob/3.12/Tools/peg_generator/pegen/sccutils.py
 */
class Scc {
    /**
     * Compute Strongly Connected Components of a directed graph.
     * @param array $vertices AbstractSet[str] The labels for the vertices
     * @param array $edges Dict[str, AbstractSet[str]] For each vertex, gives the target vertices of its outgoing edges
     * @return \Traversable Iterator[AbstractSet[str]] An iterator yielding strongly connected components, each represented as a set of vertices.  Each input vertex will occur exactly once; vertices not part of a SCC are returned as singleton sets. From http://code.activestate.com/recipes/578507/
     */
    public static function stronglyConnectedComponents(array $vertices, array $edges): iterable {
        // identified: Set[str] = set()
        $identified = [];
        // stack: List[str] = []
        $stack = [];
        // index: Dict[str, int] = {}
        $index = [];
        // boundaries: List[int] = []
        $boundaries = [];
        foreach ($vertices as $v) {
            if (!isset($index[$v])) {
                [$result, $identified, $stack, $index, $boundaries] = self::dfs($edges, $v, $identified, $stack, $index, $boundaries);
                yield from $result;
            }
        }
    }

    // dfs(v: str) -> Iterator[Set[str]]:
    private static function dfs(array $edges, string $vertex, array $identified, array $stack, array $index, array $boundaries): Generator {
        $index[$vertex] = count($stack);
        $stack[] = $vertex;
        $boundaries[] = $index[$vertex];
        foreach ($edges[$vertex] as $w) {
            if (!isset($index[$w])) {
                yield from self::dfs($edges, $vertex, $identified, $stack, $index, $boundaries);
            } elseif (!isset($identified[$w])) {
                while ($index[$w] < $boundaries[count($boundaries) - 1]) {
                    array_pop($boundaries);
                }
            }
        }
        if ($boundaries[count($boundaries) - 1] == $index[$vertex]) {
            array_pop($boundaries);
            $scc = array_unique(array_slice($stack, $index[$vertex]));
            // del stack[index[v] :]
            $stack = array_slice($stack, 0, $index[$vertex]);
            //identified.update(scc)
            $identified = array_unique(array_merge($identified, $scc));
            yield $scc;
        }
    }
/*
def topsort(
    data: Dict[AbstractSet[str], Set[AbstractSet[str]]]
) -> Iterable[AbstractSet[AbstractSet[str]]]:
    """Topological sort.

    Args:
      data: A map from SCCs (represented as frozen sets of strings) to
            sets of SCCs, its dependencies.  NOTE: This data structure
            is modified in place -- for normalization purposes,
            self-dependencies are removed and entries representing
            orphans are added.

    Returns:
      An iterator yielding sets of SCCs that have an equivalent
      ordering.  NOTE: The algorithm doesn't care about the internal
      structure of SCCs.

    Example:
      Suppose the input has the following structure:

        {A: {B, C}, B: {D}, C: {D}}

      This is normalized to:

        {A: {B, C}, B: {D}, C: {D}, D: {}}

      The algorithm will yield the following values:

        {D}
        {B, C}
        {A}

    From http://code.activestate.com/recipes/577413/.
    """
    # TODO: Use a faster algorithm?
    for k, v in data.items():
        v.discard(k)  # Ignore self dependencies.
    for item in set.union(*data.values()) - set(data.keys()):
        data[item] = set()
    while True:
        ready = {item for item, dep in data.items() if not dep}
        if not ready:
            break
        yield ready
        data = {item: (dep - ready) for item, dep in data.items() if item not in ready}
    assert not data, "A cyclic dependency exists amongst %r" % data

*/

    /**
     * Find cycles in SCC emanating from start.
     * Yields lists of the form ['A', 'B', 'C', 'A'], which means there's a path from A -> B -> C -> A.  The first item is always the start argument, but the last item may be another element, e.g.  ['A', 'B', 'C', 'B'] means there's a path from A to B and there's a cycle from B to C and back.
     * (graph: Dict[str, AbstractSet[str]], scc: AbstractSet[str], start: str) -> Iterable[List[str]]
     */
    public static function findCyclesInScc(array $graph, $scc, $start) {
        throw new NotImplementedException();
/*
    # Basic input checks.
    assert start in scc, (start, scc)
    assert scc <= graph.keys(), scc - graph.keys()

    # Reduce the graph to nodes in the SCC.
    graph = {src: {dst for dst in dsts if dst in scc} for src, dsts in graph.items() if src in scc}
    assert start in graph

    # Recursive helper that yields cycles.
    def dfs(node: str, path: List[str]) -> Iterator[List[str]]:
        if node in path:
            yield path + [node]
            return
        path = path + [node]  # TODO: Make this not quadratic.
        for child in graph[node]:
            yield from dfs(child, path)

    yield from dfs(start, [])

 */
    }
}