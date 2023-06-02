<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use function Morpho\Base\requireFile;
use function spl_autoload_register;
use function spl_autoload_unregister;

abstract class Autoloader {
    public function autoload(string $class): bool {
        $filePath = $this->filePath($class);
        if ($filePath) {
            requireFile($filePath);
            return true;
        }
        return false;
    }

    /**
     * @return string|false The path of class (string) or false otherwise.
     */
    abstract public function filePath(string $class): string|false;

    public function register(bool $prepend = false): void {
        spl_autoload_register([$this, 'autoload'], true, $prepend);
    }

    public function unregister(): void {
        spl_autoload_unregister([$this, 'autoload']);
    }
}
