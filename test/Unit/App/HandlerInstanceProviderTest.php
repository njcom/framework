<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App;

use Morpho\App\BackendModule;
use Morpho\App\HandlerInstanceProvider;
use Morpho\App\ModuleIndex;
use Morpho\App\Web\Request;
use Morpho\Base\IServiceManager;
use Morpho\Testing\TestCase;

class HandlerInstanceProviderTest extends TestCase {
    public function testInvoke() {
        $serviceManager = $this->createMock(IServiceManager::class);

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

        $handlerInstanceProvider = new HandlerInstanceProvider($serviceManager);

        $controllerClass = __NAMESPACE__ . '\\HandlerInstanceProviderTest_TestController';

        $handler = [
            'module' => $moduleName,
            'class'  => $controllerClass,
        ];

        $request = $this->createMock(Request::class);
        $request->expects($this->any())
            ->method('handler')
            ->willReturn($handler);

        $instance = $handlerInstanceProvider($request);

        $this->assertInstanceOf($controllerClass, $instance);
    }
}

class HandlerInstanceProviderTest_TestController {
}
