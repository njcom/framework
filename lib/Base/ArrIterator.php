<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Base;

use ArrayIterator as BaseArrayIt;

class ArrIterator extends BaseArrayIt {
    public function toArr(): array {
        return $this->getArrayCopy();
    }

    public function item($offset) {
        return $this->offsetGet($offset);
    }

    public function clear(): void {
        for ($this->rewind(); $this->valid(); $this->offsetUnset($this->key())) {
        }
    }

    public function isEmpty(): bool {
        return $this->count() === 0;
    }
}
