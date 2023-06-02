<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Testing;

use ArrayObject;
use UnexpectedValueException;

use function array_merge;

class VfsEntryStat extends ArrayObject {
    private array $default = [
        'dev'     => 0,
        'ino'     => 0,
        'mode'    => 0,
        'nlink'   => 0,
        'uid'     => 0,
        'gid'     => 0,
        'rdev'    => 0,
        'size'    => 0,
        'atime'   => 0,
        'mtime'   => 0,
        'ctime'   => 0,
        'blksize' => -1,
        'blocks'  => -1,
    ];

    public function __construct(array $values = []) {
        parent::__construct(array_merge($this->default, $values));
    }

    public function offsetSet(mixed $name, mixed $value): void {
        if (!isset($this->default[$name])) {
            throw new UnexpectedValueException();
        }
        parent::offsetSet($name, $value);
    }
}
