<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

class Site implements ISite {
    public readonly string $name;

    public readonly string $moduleName;

    public readonly string $hostName;

    protected array $allModulesConf;

    public function __construct(string $name, string $moduleName, array $allModulesConf, string $hostName) {
        $this->name = $name;
        $this->moduleName = $moduleName;
        $this->allModulesConf = $allModulesConf;
        $this->hostName = $hostName;
    }

    public function conf(): array {
        return $this->allModulesConf[$this->moduleName];
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
}
