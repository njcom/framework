<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\Peg\Peg;

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

    public function parseString(...$args) {
        return Peg::parse(...$args)[0];
    }

    public function generateParser(...$args): array {
        return Peg::generateParser(...$args);
    }
}