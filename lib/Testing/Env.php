<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Testing;

use Morpho\Base\TSingleton;

use const Morpho\App\BACKEND_DIR_NAME;
use const Morpho\Test\BASE_DIR_PATH;

class Env {
    use TSingleton;

    private string $baseDirPath;

    public function backendModuleDir(): iterable {
        $baseDirPath = BASE_DIR_PATH;
        foreach (glob($baseDirPath . '/' . BACKEND_DIR_NAME . '/[0-9a-z]*') as $path) {
            if (is_dir($path)) {
                yield $path;
            }
        }
    }

    public function isCi(): bool {
        return !empty(getenv('MORPHO_CI'));
    }
}