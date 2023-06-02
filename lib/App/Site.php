<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use Throwable;

class Site implements ISite {
    protected string $name;

    protected string $moduleName;

    protected array $allModulesConf;

    private string $hostName;

    public function __construct(string $name, string $moduleName, array $allModulesConf, string $hostName) {
        $this->name = $name;
        $this->moduleName = $moduleName;
        $this->allModulesConf = $allModulesConf;
        $this->hostName = $hostName;
    }

    public function name(): string {
        return $this->name;
    }

    public function moduleName(): string {
        return $this->moduleName;
    }

    public function conf(): array {
        return $this->allModulesConf[$this->moduleName];
    }

    public function hostName(): string {
        return $this->hostName;
    }

    public function backendModuleDirPath(): iterable {
        $moduleDirPaths = [];
        foreach ($this->allModulesConf as $moduleName => $moduleConf) {
            $moduleDirPaths[] = $moduleConf['paths']['dirPath'];
        }
        return $moduleDirPaths;
    }

    public function moduleConf(string $moduleName): array {
        return $this->allModulesConf[$moduleName];
    }

    public function __invoke(mixed $serviceManager): IResponse|false {
        try {
            /** @var IRequest $request */
            $request = $serviceManager['request'];
            $serviceManager['router']->route($request);
            $serviceManager['dispatcher']->dispatch($request);
            $response = $request->response();
            $response->send();
            return $response;
        } catch (Throwable $e) {
            $errorHandler = $serviceManager['errorHandler'];
            $errorHandler->handleException($e);
            //$this->trigger(new Event('error', ['exception' => $e]));
            return false;
        }
    }
}
