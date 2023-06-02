<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use Morpho\Base\IServiceManager;

use function ini_set;
use function is_array;

abstract class AppInitializer {
    protected IServiceManager $serviceManager;

    public function __construct(IServiceManager $serviceManager) {
        $this->serviceManager = $serviceManager;
    }

    public abstract function init(): void;

    protected function applySiteConf($siteConf): void {
        if (isset($siteConf['iniConf'])) {
            $this->applyIniConf($siteConf['iniConf']);
        }
    }

    protected function applyIniConf(array $iniConf, $parentName = null): void {
        foreach ($iniConf as $name => $value) {
            $settingName = $parentName ? $parentName . '.' . $name : $name;
            if (is_array($value)) {
                $this->applyIniConf($value, $settingName);
            } else {
                ini_set($settingName, $value);
            }
        }
    }
}