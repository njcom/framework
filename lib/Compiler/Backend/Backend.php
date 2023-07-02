<?php

declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Backend;

use Morpho\Base\NotImplementedException;

class Backend {
    public function __invoke(mixed $context): mixed {
        do {
            $context = $this->optimizer($context);
            $context = $this->codegen($context);
        } while (!$this->done($context));
        return $context;
    }

    public function optimizer(mixed $context): callable {
        throw new NotImplementedException();
    }

    public function codegen(mixed $context): callable {
        throw new NotImplementedException();
    }

    private function done(mixed $context): bool {
        return true;
    }
}