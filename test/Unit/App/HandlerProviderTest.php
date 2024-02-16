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
use Morpho\App\Web\Request;
use Morpho\Base\ServiceManager;
use Morpho\Testing\TestCase;

class HandlerProviderTest extends TestCase {
    public function testInvoke() {
        $serviceManager = $this->createMock(ServiceManager::class);

        $moduleName = 'foo/bar';
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

        $handlerProvider = new HandlerProvider($serviceManager);

        $controllerClass = __NAMESPACE__ . '\\HandlerProviderTest_TestController';

        $handler = [
            'module' => $moduleName,
            'class'  => $controllerClass,
        ];

        $request = $this->createMock(Request::class);
        $request->expects($this->any())
            ->method('handler')
            ->willReturn($handler);

        $instance = $handlerProvider($request);

        $this->assertInstanceOf($controllerClass, $instance);
    }
}

class HandlerProviderTest_TestController {
    public function __invoke() {
    }
}
