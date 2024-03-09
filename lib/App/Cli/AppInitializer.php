<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Cli;

use Morpho\App\AppInitializer as BaseAppInitializer;

class AppInitializer extends BaseAppInitializer {
    public function init(): void {
        Env::init();
        $siteConf = $this->serviceManager['site']->conf();
        $this->applySiteConf($siteConf);
        $this->serviceManager['errorHandler']->register();
    }
}
