<?php

declare(strict_types=1);
namespace Morpho\Test\Unit\Tech\Php\PhpFileHeaderFixerTest;

/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */

use Morpho\App\App;
use Morpho\Testing\TestCase;

class AppTest extends TestCase {
    public function testConfAccessors() {
        $app = new App();
        $this->assertEquals([], $app->conf());
        $newConf = ['foo' => 'bar'];
        $app = new App($newConf);
        $this->assertSame($newConf, $app->conf());
        $newConf = ['color' => 'orange'];
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertNull($app->setConf($newConf));
        $this->assertSame($newConf, $app->conf());
    }
}