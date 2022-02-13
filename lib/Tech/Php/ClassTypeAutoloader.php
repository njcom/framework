<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */

namespace Morpho\Tech\Php;

use InvalidArgumentException;

use function array_merge;
use function call_user_func_array;
use function defined;
use function file_exists;
use function stream_resolve_include_path;
use function strlen;
use function strpos;
use function strrpos;
use function strtr;
use function substr;

/**
 * Changed ClassLoader from the Composer project with adapted coding conventions,
 * removed support of the fallback directories and outdated code.
 *
 * https://github.com/composer/composer/blob/master/src/Composer/Autoload/ClassLoader.php
 * Copyright (c) 2015 Nils Adermann, Jordi Boggiano
 */
class ClassTypeAutoloader extends Autoloader {
    private $classToFileMap = [];
    private $classToFileMapAuthoritative = false;

    // PSR-0
    private $prefixesPsr0 = [];
    private $useIncludePath = false;

    // PSR-4
    private $prefixLengthsPsr4 = [];
    private $prefixDirsPsr4 = [];

    // ------------------------------------------------------------------------
    // ClassToFilePath mapping:

    public function classToFilePathMap(): array {
        return $this->classToFileMap;
    }

    public function addClassToFilePathMap(array $classToFilePathMap): static {
        if ($this->classToFileMap) {
            $this->classToFileMap = array_merge($this->classToFileMap, $classToFilePathMap);
        } else {
            $this->classToFileMap = $classToFilePathMap;
        }
        return $this;
    }

    /**
     * Should the include be used during class search?
     *
     * @param $useIncludePath bool|null
     */
    public function useIncludePath($useIncludePath = null): bool {
        if (null !== $useIncludePath) {
            $this->useIncludePath = $useIncludePath;
        }
        return $this->useIncludePath;
    }

    /**
     * Should class lookup fail if not found in the current class map?
     *
     * @param $classToFileMapAuthoritative bool|null
     */
    public function isClassToFileMapAuthoritative($classToFileMapAuthoritative = null): bool {
        if (null !== $classToFileMapAuthoritative) {
            $this->classToFileMapAuthoritative = $classToFileMapAuthoritative;
        }
        return $this->classToFileMapAuthoritative;
    }

    // ------------------------------------------------------------------------
    // PSR-0 mapping:

    public function prefixesPsr0(): array {
        return !empty($this->prefixesPsr0)
            ? call_user_func_array('array_merge', $this->prefixesPsr0)
            : [];
    }

    public function addPrefixToDirPathMappingPsr0(string $prefix, $paths, bool $prepend = false): static {
        $first = $prefix[0];
        if (!isset($this->prefixesPsr0[$first][$prefix])) {
            $this->prefixesPsr0[$first][$prefix] = (array) $paths;
        } else {
            $this->prefixesPsr0[$first][$prefix] = $prepend
                ? array_merge((array) $paths, $this->prefixesPsr0[$first][$prefix])
                : array_merge($this->prefixesPsr0[$first][$prefix], (array) $paths);
        }
        return $this;
    }

    public function setPrefixToDirPathMappingPsr0(string $prefix, $paths): static {
        $first = $prefix[0];
        $this->prefixesPsr0[$first][$prefix] = (array) $paths;
        return $this;
    }

    // ------------------------------------------------------------------------
    // PSR-4 mapping:

    public function prefixesPsr4(): array {
        return $this->prefixDirsPsr4;
    }

    public function addPrefixToDirPathMappingPsr4(string $prefix, $paths, $prepend = false): static {
        if (!isset($this->prefixDirsPsr4[$prefix])) {
            $this->setPrefixToDirPathMappingPsr4($prefix, $paths);
        } else {
            $this->prefixDirsPsr4[$prefix] = $prepend
                ? array_merge((array) $paths, $this->prefixDirsPsr4[$prefix])
                : array_merge($this->prefixDirsPsr4[$prefix], (array) $paths);
        }
        return $this;
    }

    public function setPrefixToDirPathMappingPsr4(string $prefix, $paths): static {
        $length = strlen($prefix);
        if ('\\' !== $prefix[$length - 1]) {
            throw new InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
        }
        $this->prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
        $this->prefixDirsPsr4[$prefix] = (array) $paths;
        return $this;
    }

    public function filePath(string $class): string|false {
        // class map lookup
        if (isset($this->classToFileMap[$class])) {
            return $this->classToFileMap[$class];
        }
        if ($this->classToFileMapAuthoritative) {
            return false;
        }

        $filePath = $this->findFilePathWithExtension($class, '.php');

        // Search for Hack files if we are running on HHVM
        if ($filePath === null && defined('HHVM_VERSION')) {
            $filePath = $this->findFilePathWithExtension($class, '.hh');
        }

        if ($filePath === null) {
            // Remember that this class does not exist.
            return $this->classToFileMap[$class] = false;
        }

        return $filePath;
    }

    /**
     * @return string|null
     */
    private function findFilePathWithExtension(string $class, string $ext) {
        // PSR-4 lookup
        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . $ext;
        $first = $class[0];
        if (isset($this->prefixLengthsPsr4[$first])) {
            foreach ($this->prefixLengthsPsr4[$first] as $prefix => $length) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($this->prefixDirsPsr4[$prefix] as $dir) {
                        if (file_exists($file = $dir . DIRECTORY_SEPARATOR . substr($logicalPathPsr4, $length))) {
                            return $file;
                        }
                    }
                }
            }
        }

        // PSR-0 lookup
        if (false !== $pos = strrpos($class, '\\')) {
            // namespaced class name
            $logicalPathPsr0 = substr($logicalPathPsr4, 0, $pos + 1)
                . strtr(substr($logicalPathPsr4, $pos + 1), '_', DIRECTORY_SEPARATOR);
        } else {
            // PEAR-like class name
            $logicalPathPsr0 = strtr($class, '_', DIRECTORY_SEPARATOR) . $ext;
        }

        if (isset($this->prefixesPsr0[$first])) {
            foreach ($this->prefixesPsr0[$first] as $prefix => $dirs) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($dirs as $dir) {
                        if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr0)) {
                            return $file;
                        }
                    }
                }
            }
        }
        // PSR-0 include paths.
        if ($this->useIncludePath && $file = stream_resolve_include_path($logicalPathPsr0)) {
            return $file;
        }
    }
}
