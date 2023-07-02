<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use Morpho\Base\IHasServiceManager;
use Morpho\Base\IServiceManager;

class HandlerProvider {
    protected ModuleIndex $moduleIndex;

    private array $registeredModules = [];

    private IServiceManager $serviceManager;

    public function __construct(IServiceManager $serviceManager) {
        $this->moduleIndex = $serviceManager['backendModuleIndex'];
        $this->serviceManager = $serviceManager;
    }

    public function __invoke(IRequest $request): callable {
        $handler = $request->handler();

        $module = $this->moduleIndex->module($handler['module']);

        // @TODO: Register simple common autoloader, which must try to load the class using simple scheme, then call Composer's autoloader in case of failure.
        $this->registerModuleClassLoader($module);

        $instance = new $handler['class'];
        if ($instance instanceof IHasServiceManager) {
            $instance->setServiceManager($this->serviceManager);
        }

        $handler['instance'] = $instance;

        $request->setHandler($handler);

        return $instance;
    }

    protected function registerModuleClassLoader(Module $module): void {
        $moduleName = $module->name();
        if (!isset($this->registeredModules[$moduleName])) {
            require_once $module->autoloadFilePath();
            $this->registeredModules[$moduleName] = true;
        }
    }
}
