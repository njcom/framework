<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App;

use ArrayObject;
use Morpho\App\App;
use Morpho\Base\IServiceManager;
use Morpho\Testing\TestCase;
use RuntimeException;

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

    public function testInitTwice_ReturnsTheSameServiceManagerInstance() {
        $serviceManager1 = new class extends ArrayObject implements IServiceManager {
            public function setConf(mixed $conf): static {
                throw new RuntimeException();
            }

            public function conf(): mixed {
                throw new RuntimeException();
            }
        };
        $app = new SimpleApp($serviceManager1);
        $this->assertSame($serviceManager1, $app->init());
        $this->assertSame($serviceManager1, $app->init());
    }
}

class SimpleApp extends App {
    private IServiceManager $serviceManager;

    public function __construct(IServiceManager $serviceManager) {
        parent::__construct();
        $this->serviceManager = $serviceManager;
    }

    protected function _init(): IServiceManager {
        return $this->serviceManager;
    }
}