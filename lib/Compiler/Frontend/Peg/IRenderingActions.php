<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

interface IRenderingActions {
    //  Global flag whether we want actions in __str__() -- default off.
    // SIMPLE_STR = True
    public function renderActions($flag = null): bool;
}