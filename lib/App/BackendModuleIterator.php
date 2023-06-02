<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use IteratorAggregate;
use Traversable;
use Morpho\Fs\File;

use function is_file;
use function trim;

class BackendModuleIterator implements IteratorAggregate {
    private ISite $site;

    public function __construct(ISite $site) {
        $this->site = $site;
    }

    public function getIterator(): Traversable {
        foreach ($this->site->backendModuleDirPath() as $moduleDirPath) {
            $metaFilePath = $moduleDirPath . '/' . META_FILE_NAME;
            if (!is_file($metaFilePath)) {
                continue;
            }
            $metaFileModuleConf = File::readJson($metaFilePath);
            $moduleConf = $this->site->moduleConf($metaFileModuleConf['name']);
            $metaFileModuleConf['paths'] = array_merge($moduleConf['paths'], ['dirPath' => $moduleDirPath]);
            if (!$this->filter($metaFileModuleConf)) {
                continue;
            }
            yield $this->map($metaFileModuleConf);
        }
    }

    protected function filter(array $module): bool {
        return isset($module['name']);
    }

    protected function map(array $module): array {
        $namespaces = [];
        foreach ($module['autoload']['psr-4'] ?? [] as $key => $value) {
            $namespaces[trim($key, '\\/')] = trim($value, '\\/');
        }
        $moduleName = $module['name'];
        return [
            'name'      => $moduleName,
            'paths'     => $module['paths'],
            'namespace' => $namespaces,
            'weight'    => 0,
        ];
    }
}
