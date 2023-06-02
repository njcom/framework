<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Base;

use ArrayAccess;
use Countable;
use Iterator;
use IteratorAggregate;
use Serializable;

/**
 * https://www.php.net/manual/class.arrayobject.php
 */
interface IArrayObject extends IteratorAggregate, ArrayAccess, Serializable, Countable {
    public function append(mixed $value): void;
    public function asort(int $flags = SORT_REGULAR): bool;
    public function count(): int;
    public function exchangeArray(array|object $array): array;
    public function getArrayCopy(): array;
    public function getFlags(): int;
    public function getIterator(): Iterator;
    public function getIteratorClass(): string;
    public function ksort(int $flags = SORT_REGULAR): bool;
    public function natcasesort(): bool;
    public function natsort(): bool;
    public function offsetExists(mixed $key): bool;
    public function offsetGet(mixed $key): mixed;
    public function offsetSet(mixed $key, mixed $value): void;
    public function offsetUnset(mixed $key): void;
    public function serialize(): string;
    public function setFlags(int $flags): void;
    public function setIteratorClass(string $iteratorClass): void;
    public function uasort(callable $callback): bool;
    public function uksort(callable $callback): bool;
    public function unserialize(string $data): void;
}