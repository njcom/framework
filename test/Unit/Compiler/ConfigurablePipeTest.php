<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler;

use Morpho\Compiler\Compiler;
use Morpho\Testing\TestCase;

class ConfigurablePipeTest extends TestCase {
    public function testConfAccessors() {
        $compiler = new Compiler();
        $this->checkAccessors([$compiler, 'conf'], [], ['foo' => 'bar'], $compiler);
    }
}