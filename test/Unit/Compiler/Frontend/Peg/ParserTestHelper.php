<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\Peg\Grammar;
use Morpho\Compiler\Frontend\Peg\GrammarParser;
use Morpho\Compiler\Frontend\Peg\GrammarTokenizer;
use Morpho\Compiler\Frontend\Peg\Peg;
use Morpho\Compiler\Frontend\Peg\Tokenizer;

class ParserTestHelper {
    public function sortRecursive(array $val): array {
        ksort($val);
        $sortedKeys = array_keys($val);
        array_multisort($val);
        $sorted = [];
        foreach ($sortedKeys as $key) {
            $v = $val[$key];
            $sorted[$key] = is_array($v) ? $this->sortRecursive($v) : $v;
        }
        return $sorted;
    }

    public function parseString(string $s, string $parserClass = null): Grammar {
        // @todo
        return Peg::parse($s, $parserClass)[0];
        /*
        $tokenizer = new GrammarTokenizer(Tokenizer::tokenize($s));
        $parser = new GrammarParser($tokenizer);
        $grammar = $parser->start();
        if (!$grammar) {
            throw $parser->mkSyntaxError('Unable to parse grammar');
        }
        return $grammar;
        */
    }

    public function generateParser(Grammar $grammar): string {
        // @todo
        return Peg::generateParser($grammar);
    }
}