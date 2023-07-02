<?php declare(strict_types=1);

namespace Morpho\Test\Unit\Tech\Php\PhpFileHeaderFixerTest;

class Foo {
    public function __invoke(mixed $val): mixed {
        return null;
    }
}