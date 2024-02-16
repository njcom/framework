<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App;

use Morpho\App\App;
use Morpho\Base\ServiceManager;
use Morpho\Testing\TestCase;

class AppTest extends TestCase {
    public function testConfAccessors() {
        $app = new App();
        $this->assertEquals([], $app->conf);
        $newConf = ['foo' => 'bar'];
        $app = new App($newConf);
        $this->assertSame($newConf, $app->conf);
    }

    public function testInitTwice_ReturnsTheSameServiceManagerInstance() {
        $serviceManager = new ServiceManager();
        $app = new SimpleApp($serviceManager);
        $this->assertSame($serviceManager, $app->init());
        $this->assertSame($serviceManager, $app->init());
    }
}

class SimpleApp extends App {
    private ServiceManager $serviceManager;

    public function __construct(ServiceManager $serviceManager) {
        parent::__construct();
        $this->serviceManager = $serviceManager;
    }

    protected function _init(): ServiceManager {
        return $this->serviceManager;
    }
}