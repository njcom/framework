<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler\Frontend;

use Morpho\Compiler\Frontend\Frontend;
use Morpho\Test\Unit\Compiler\ConfigurablePipeTest;

class FrontendTest extends ConfigurablePipeTest {
    public function testInterface() {
        $frontend = new Frontend();
        $this->assertIsCallable($frontend);
    }
}