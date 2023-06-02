<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Base;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Exposes all properties of the class for `foreach` looop.
 */
class Enumerable implements IteratorAggregate {
    public function getIterator(): Traversable {
        return new ArrayIterator($this->enumerableProps());
    }

    protected function enumerableProps(): array {
        return get_object_vars($this);
    }
}