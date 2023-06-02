<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Base\IFn;

class GrammarChecker implements IFn {
    public function __construct() {
    }

    public function __invoke(mixed $val): mixed {
        return $val;
    }
}