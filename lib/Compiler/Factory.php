<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler;

use Morpho\Compiler\Backend\Backend;
use Morpho\Compiler\Frontend\Frontend;

class Factory implements IFactory {
    public function mkFrontend(): callable {
        return new Frontend();
    }

    public function mkMidend(): callable {
        // Middle end by default does nothing.
        return fn ($v) => $v;
    }

    public function mkBackend(): callable {
        return new Backend();
    }
}