<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test;

use Morpho\Testing\Env;

use function date_default_timezone_set;
use function is_file;

use const Morpho\App\{AUTOLOAD_FILE_NAME, VENDOR_DIR_NAME};

date_default_timezone_set('UTC');
define(__NAMESPACE__ . '\\BASE_DIR_PATH', dirname(__DIR__));

(function () {
    /*
    $classLoader = require __DIR__ . '/../vendor/autoload.php';
    $classLoader->addPsr4(__NAMESPACE__ . '\\', __DIR__);
    */
    foreach (Env::instance()->backendModuleDir() as $moduleDirPath) {
        $autoloadFilePath = $moduleDirPath . '/' . VENDOR_DIR_NAME . '/' . AUTOLOAD_FILE_NAME;
        if (is_file($autoloadFilePath)) {
            require $autoloadFilePath;
        }
    }
})();