<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use ArrayAccess;

class Frame implements ArrayAccess {
    protected $function;

    protected $line;

    protected $filePath;

    public function __construct(array $conf) {
        foreach ($conf as $name => $value) {
            $this->$name = $value;
        }
    }

    public function offsetExists(mixed $offset): bool {
        return isset($this->$offset);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->$offset;
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->$offset = $value;
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->$offset);
    }

    public function __toString(): string {
        $filePath = isset($this->filePath) ? $this->filePath : 'unknown';
        $line = isset($this->line) ? $this->line : 'unknown';
        $function = isset($this->function) ? $this->function : 'unknown';

        return $function . " called at [$filePath:$line]";
    }
}
