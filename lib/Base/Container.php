<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Base;

abstract class Container implements IContainer {
    protected readonly mixed $val;

    public function __construct(mixed $val) {
        $this->val = $val;
    }

    public function val(): mixed {
        return $this->val;
    }
}
