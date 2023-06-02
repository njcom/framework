<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use Morpho\Fs\Dir;
use Morpho\Fs\Path;
use RuntimeException;

use function is_dir;
use const PHP_SAPI;

class BackendModule extends Module {
    /**
     * Detects and returns base directory path of the module.
     * @param string $scriptDirPath Any directory path within the module.
     * @param bool $throwEx
     * @return false|string
     */
    public static function findBaseDir(string $scriptDirPath, bool $throwEx = true): string|bool {
        $baseDirPath = false;
        do {
            $path = $scriptDirPath . '/vendor/composer/ClassLoader.php';
            if (is_file($path)) {
                $baseDirPath = $scriptDirPath;
                break;
            } else {
                $chunks = explode(DIRECTORY_SEPARATOR, $scriptDirPath, -1);
                $scriptDirPath = implode(DIRECTORY_SEPARATOR, $chunks);
            }
        } while ($chunks);
        if (false === $baseDirPath) {
            if ($throwEx) {
                throw new RuntimeException("Unable to detect the base directory of a module");
            }
            return false;
        }
        return Path::normalize($baseDirPath);
    }

    public function autoloadFilePath(): string {
        return $this->vendorDirPath() . '/' . AUTOLOAD_FILE_NAME;
    }

    public function vendorDirPath(): string {
        return $this->dirPath() . '/' . VENDOR_DIR_NAME;
    }

    public function rcDirPath(): string {
        if (isset($this['paths']['rcDirPath'])) {
            return $this['paths']['rcDirPath'];
        }
        return $this['paths']['rcDirPath'] = $this->dirPath() . '/' . RC_DIR_NAME;
    }

    public function binDirPath(): string {
        if (isset($this['paths']['binDirPath'])) {
            return $this['paths']['binDirPath'];
        }
        return $this['paths']['binDirPath'] = $this->dirPath() . '/' . BIN_DIR_NAME;
    }

    public function logDirPath(): string {
        if (isset($this['paths']['logDirPath'])) {
            return $this['paths']['logDirPath'];
        }
        return $this['paths']['logDirPath'] = $this->dirPath() . '/' . LOG_DIR_NAME;
    }

    public function compiledTemplatesDirPath(): string {
        return $this->cacheDirPath() . '/template-engine';
    }

    public function cacheDirPath(): string {
        if (isset($this['paths']['cacheDirPath'])) {
            return $this['paths']['cacheDirPath'];
        }
        return $this['paths']['cacheDirPath'] = $this->dirPath() . '/' . CACHE_DIR_NAME;
    }

    public function viewDirPath(): string {
        if (isset($this['paths']['viewDirPath'])) {
            return $this['paths']['viewDirPath'];
        }
        return $this['paths']['viewDirPath'] = $this->dirPath() . '/' . VIEW_DIR_NAME;
    }

    public function confDirPath(): string {
        if (isset($this['paths']['confDirPath'])) {
            return $this['paths']['confDirPath'];
        }
        return $this['paths']['confDirPath'] = $this->dirPath() . '/' . CONF_DIR_NAME;
    }

    public function tmpDirPath(): string {
        if (isset($this['paths']['tmpDirPath'])) {
            return $this['paths']['tmpDirPath'];
        }
        return $this['paths']['tmpDirPath'] = $this->dirPath() . '/' . TMP_DIR_NAME;
    }

    public function testDirPath(): string {
        if (isset($this['paths']['testDirPath'])) {
            return $this['paths']['testDirPath'];
        }
        return $this['paths']['testDirPath'] = $this->dirPath() . '/' . TEST_DIR_NAME;
    }

    /**
     * @param bool $differentiateSapi Either return only SAPI specific paths or not.
     * @return iterable Iterable over file paths of controllers.
     */
    public function controllerFilePaths(bool $differentiateSapi): iterable {
        $paths = $this['paths'];
        if (isset($paths['controllerDirPath'])) {
            $controllerDirPaths = (array) $paths['controllerDirPath'];
        } else {
            $libDirPath = $this->libDirPath();
            if ($differentiateSapi) {
                $controllerDirPaths = PHP_SAPI === 'cli' ? [$libDirPath . '/App/Cli'] : [$libDirPath . '/App/Web'];
            } else {
                $controllerDirPaths = [$libDirPath . '/App/Web', $libDirPath . '/App/Cli'];
            }
        }
        foreach ($controllerDirPaths as $controllerDirPath) {
            if (!is_dir($controllerDirPath)) {
                continue;
            }
            foreach (Dir::filePaths($controllerDirPath, true) as $filePath) {
                if (preg_match('~\w' . CONTROLLER_SUFFIX . '\.php$~', $filePath)) {
                    yield $filePath;
                }
            }
        }
    }

    public function libDirPath(): string {
        if (isset($this['paths']['libDirPath'])) {
            return $this['paths']['libDirPath'];
        }
        return $this['paths']['libDirPath'] = $this->dirPath() . '/' . LIB_DIR_NAME;
    }
}
