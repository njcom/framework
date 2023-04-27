<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Base\NotImplementedException;

/**
 * Based on https://github.com/python/cpython/blob/main/Lib/ast.py#L55
 */
class Ast {
    public static function literalEval($nodeOrString) {
        throw new NotImplementedException();
    }
}