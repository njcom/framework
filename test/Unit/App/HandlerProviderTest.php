<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App;

use Morpho\App\BackendModule;
use Morpho\App\HandlerProvider;
use Morpho\App\ModuleIndex;
use Morpho\Base\IFn;
use Morpho\Base\ServiceManager;
use Morpho\Testing\TestCase;

class HandlerProviderTest extends TestCase {
    public function testInvoke() {
        $moduleName = 'foo/bar';
        $serviceManager = $this->mkServiceManagerMock($moduleName);
        $handlerProvider = new HandlerProvider($serviceManager);
        $controllerClass = __NAMESPACE__ . '\\HandlerProviderTest_TestController';
        $handler = [
            'module' => $moduleName,
            'class'  => $controllerClass,
            'method' => '__invoke',
        ];
        $context = new class {
            public array $handler;
        };
        $context->handler = $handler;

        $callback = $handlerProvider($context);

        $this->assertIsCallable($callback);
        $this->assertInstanceOf($handler['class'], $callback[0]);
        $this->assertSame($handler['method'], $callback[1]);
    }

    public function testInterface() {
        $serviceManager = $this->createMock(ServiceManager::class);
        $services = [
            'backendModuleIndex' => null,
        ];
        $serviceManager->expects($this->any())
            ->method('offsetGet')
            ->willReturnCallback(
                function ($id) use ($services) {
                    return $services[$id];
                }
            );
        $this->assertInstanceOf(IFn::class, new HandlerProvider($this->mkServiceManagerMock('foo/bar')));
    }

    private function mkServiceManagerMock(string $moduleName) {
        $serviceManager = $this->createMock(ServiceManager::class);
        $module = $this->createConfiguredMock(
            BackendModule::class,
            ['name' => $moduleName, 'autoloadFilePath' => __FILE__]
        );

        $moduleIndex = $this->createMock(ModuleIndex::class);
        $moduleIndex->expects($this->any())
            ->method('module')
            ->with($moduleName)
            ->willReturn($module);
        $services = [
            'backendModuleIndex' => $moduleIndex,
        ];
        $serviceManager->expects($this->any())
            ->method('offsetGet')
            ->willReturnCallback(
                function ($id) use ($services) {
                    return $services[$id];
                }
            );
        return $serviceManager;
    }
}

class HandlerProviderTest_TestController {
    public function __invoke() {
    }
}
