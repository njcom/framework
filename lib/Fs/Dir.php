<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Fs;

use Closure;
use DirectoryIterator;
use FilesystemIterator;
use Generator;
use InvalidArgumentException;
use LogicException;
use Morpho\Base\Conf;
use Morpho\Base\Env;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

use function basename;
use function chdir;
use function dirname;
use function fileperms;
use function getcwd;
use function implode;
use function is_array;
use function is_bool;
use function is_callable;
use function is_dir;
use function is_file;
use function is_iterable;
use function is_link;
use function is_string;
use function mkdir;
use function preg_match;
use function preg_quote;
use function unlink;

class Dir extends Entry {
    public const PHP_FILE_RE = '~\.php$~si';

    public static function move(string $sourceDirPath, string $targetDirPath): string {
        // @TODO: why not rename()?
        self::copy($sourceDirPath, $targetDirPath);
        self::delete($sourceDirPath);
        return $targetDirPath;
    }

    public static function copy(
        string $sourceDirPath,
        string $targetDirPath,
        $processor = null,
        array $conf = null
    ): string {
        // @TODO: Handle dots and relative paths: '..', '.'
        // @TODO: Handle the case: cp module/system ../../dst/module should create ../../dst/module/system
        self::mustExist($sourceDirPath);

        if ($sourceDirPath === $targetDirPath) {
            throw new Exception("Cannot copy the directory '$sourceDirPath' into itself");
        }

        $conf = Conf::check(
            [
                'overwrite'    => false,
                'followLinks'  => false,
                'skipIfExists' => false,
            ],
            (array) $conf
        );

        if (is_dir($targetDirPath)) {
            $sourceDirName = basename($sourceDirPath);
            if ($sourceDirName !== basename($targetDirPath)) {
                $targetDirPath .= '/' . $sourceDirName;
            }
            if ($sourceDirPath === $targetDirPath) {
                throw new Exception(
                    "The '" . dirname($targetDirPath) . "' directory already contains the '$sourceDirName'"
                );
            }
        }

        $targetDirPath = self::create($targetDirPath, fileperms($sourceDirPath));

        $paths = self::paths(
            $sourceDirPath,
            $processor,
            [
                'recursive'   => false,
                'type'        => Stat::ENTRY,
                'followLinks' => $conf['followLinks'],
            ]
        );
        foreach ($paths as $path) {
            $targetPath = $targetDirPath . '/' . basename($path);
            if (is_file($path) || is_link($path)) {
                File::copy($path, $targetPath, $conf['overwrite'], $conf['skipIfExists']);
            } else {
                self::copy($path, $targetPath, $processor, $conf);
            }
        }

        return $targetDirPath;
    }

    public static function mustExist(string $dirPath): string {
        if ('' === $dirPath) {
            throw new Exception("The directory path is empty");
        }
        if (!is_dir($dirPath)) {
            throw new Exception("The '$dirPath' directory does not exist");
        }
        return $dirPath;
    }

    /**
     * @param string|array $dirPath
     * @param int|null $mode
     * @param bool $recursive
     * @return string|array string if $dirPath is a string, an array if the $dirPath is an array
     * @TODO: Accept iterable
     */
    public static function create($dirPath, ?int $mode = Stat::DIR_MODE, bool $recursive = true) {
        if (null === $mode) {
            $mode = Stat::DIR_MODE;
        }
        if (is_array($dirPath)) {
            $res = [];
            foreach ($dirPath as $key => $path) {
                $res[$key] = self::create($path, $mode, $recursive);
            }
            return $res;
        } elseif (!is_string($dirPath)) {
            throw new Exception('Invalid type of the argument');
        }

        if ('' === $dirPath) {
            throw new Exception("The directory path is empty");
        }

        if (is_dir($dirPath)) {
            return $dirPath;
        }

        if (!mkdir($dirPath, $mode, $recursive)) {
            throw new RuntimeException("Unable to create the directory '$dirPath' with mode: $mode");
        }

        return $dirPath;
    }

    public static function paths(
        string|iterable $dirPaths,
        string|null|callable $processor = null,
        array|bool|null $conf = null
    ): Generator {
        $conf = Conf::check(
            [
                'recursive'   => false,
                'followLinks' => false,
                'type'        => Stat::ENTRY,
            ],
            self::normalizeConf($conf)
        );

        if (is_string($processor)) {
            $regexp = $processor;
            $processor = function ($path, $isDir) use ($regexp) {
                return $isDir || preg_match($regexp, $path);
            };
        }
        if (is_string($dirPaths)) {
            $dirPaths = (array) $dirPaths;
        }
        $recursive = $conf['recursive'];
        foreach ($dirPaths as $dirPath) {
            foreach (new DirectoryIterator($dirPath) as $item) {
                if ($item->isDot()) {
                    continue;
                }

                $path = Path::normalize($item->getPathname());
                $isDir = $item->isDir();

                if ($isDir) {
                    $match = $conf['type'] & Stat::DIR;
                } else {
                    $match = $conf['type'] & Stat::FILE;
                }
                if (!$match) {
                    if (!$isDir || !$recursive) {
                        continue;
                    }
                } else {
                    if (null !== $processor) {
                        $modifiedPath = $processor($path, $isDir);
                        if (false === $modifiedPath) {
                            continue;
                        } elseif (true !== $modifiedPath && null !== $modifiedPath) {
                            $path = $modifiedPath;
                        }
                    }
                    yield $path;
                }

                if ($isDir && $recursive) {
                    if ($item->isLink() && !$conf['followLinks']) {
                        continue;
                    }

                    yield from self::paths($item->getPathname(), $processor, $conf);
                }
            }
        }
    }

    private static function normalizeConf(null|array|bool $conf): array {
        if (!is_array($conf)) {
            if (null === $conf) {
                $conf = [];
            } elseif (is_bool($conf)) {
                $conf = ['recursive' => $conf];
            } else {
                throw new InvalidArgumentException();
            }
        }
        return $conf;
    }

    /**
     * Deletes files and directories recursively from a file system.
     *
     * This method recursively removes the $dirPath and all its contents. You should be extremely careful with this method as it has the potential to erase everything that the current user has access to.
     *
     * @param bool|callable $predicateFnOrFlag If callable then it must return true for the all entries which will be deleted and false otherwise. If boolean it must return true if the directory $dirPath must be deleted and false otherwise.
     */
    public static function delete(string|iterable $dirPath, bool|callable $predicateFnOrFlag = true): void {
        if (is_iterable($dirPath)) {
            foreach ($dirPath as $path) {
                static::_delete($path, $predicateFnOrFlag);
            }
        } else {
            static::_delete($dirPath, $predicateFnOrFlag);
        }
    }

    private static function _delete(string $dirPath, $predicateOrDeleteSelf) {
        if (is_callable($predicateOrDeleteSelf)) {
            self::__delete($dirPath, $predicateOrDeleteSelf);
        } elseif (is_bool($predicateOrDeleteSelf)) {
            if ($predicateOrDeleteSelf) {
                // Delete self
                $predicate = null;
            } else {
                // Not delete self
                $predicate = function ($path, $isDir) use ($dirPath) {
                    return $path !== $dirPath;
                };
            }
            self::__delete($dirPath, $predicate);
        } else {
            throw new InvalidArgumentException('The second argument must be either bool or callable');
        }
    }

    /**
     * @param string $dirPath
     * @param callable|null $predicate Predicate selects entries which will be deleted.
     */
    private static function __delete(string $dirPath, ?callable $predicate): void {
        $absPath = Path::normalize(self::mustExist($dirPath));
        $it = new DirectoryIterator($absPath);
        foreach ($it as $entry) {
            if ($entry->isDot()) {
                continue;
            }
            $entryPath = $entry->getPathname();
            if (is_link($entryPath)) {
                if (!unlink($entryPath)) {
                    throw new RuntimeException("The symlink '$entryPath' can not be deleted");
                }
                clearstatcache(true, $entryPath);
                continue;
            }
            if ($entry->isDir()) {
                if (null !== $predicate) {
                    if ($predicate($entryPath, true)) {
                        // If it is a directory and we need to delete this directory, delete contents regardless of the $predicate, so pass the `null` as the second argument.
                        self::__delete($entryPath, null);
                    } else {
                        // The $predicate can be used for the directory contents, so pass it as the argument.
                        self::__delete($entryPath, $predicate);
                    }
                } else {
                    self::__delete($entryPath, null);
                }
            } else {
                if (null === $predicate || $predicate($entryPath, false)) {
                    if (!unlink($entryPath)) {
                        throw new RuntimeException("The file '$entryPath' can not be deleted, check permissions");
                    }
                    clearstatcache(true, $entryPath);
                }
            }
        }
        if (null === $predicate || $predicate($absPath, true)) {
            if (!rmdir($absPath)) {
                throw new RuntimeException(
                    "Unable to delete the directory '$absPath': it may be not empty or doesn't have relevant permissions"
                );
            }
            clearstatcache(true, $absPath);
        }
    }

    public static function copyContents($sourceDirPath, $targetDirPath): string {
        foreach (new DirectoryIterator($sourceDirPath) as $item) {
            if ($item->isDot()) {
                continue;
            }
            $entryPath = $item->getPathname();
            $relPath = Path::rel($entryPath, $sourceDirPath);
            Entry::copy($entryPath, $targetDirPath . '/' . $relPath);
        }
        return $targetDirPath;
    }

    /**
     * Shortcut for the paths() with $conf['type'] == Stat::DIR option.
     */
    public static function dirPaths(
        string|iterable $dirPath,
        string|null|callable $processor = null,
        array|bool|null $conf = null
    ): Generator {
        $conf = self::normalizeConf($conf);
        $conf['type'] = Stat::DIR;
        if (null !== $processor) {
            $processor = function ($path) use ($processor) {
                if (is_string($processor)) {
                    return (bool) preg_match($processor, $path);
                } elseif (!$processor instanceof Closure) {
                    throw new Exception("Invalid processor");
                }
                return $processor($path, true);
            };
        }
        return self::paths($dirPath, $processor, $conf);
    }

    public static function dirNames(
        string|iterable $dirPath,
        string|null|callable $processor = null,
        array|bool|null $conf = null
    ): Generator {
        $conf = self::normalizeConf($conf);
        if (!empty($conf['recursive'])) {
            throw new LogicException("The 'recursive' conf param must be false");
        }
        $conf['type'] = Stat::DIR;
        return self::names($dirPath, $processor, $conf);
    }

    public static function names(
        string|iterable $dirPath,
        string|null|callable $processor = null,
        array|bool|null $conf = null
    ): Generator {
        $conf = self::normalizeConf($conf);
        if (null !== $processor) {
            $processor = function ($path) use ($processor) {
                $baseName = basename($path);
                if (is_string($processor)) {
                    if (preg_match($processor, $baseName)) {
                        return $baseName;
                    }
                    return false;
                } elseif (!$processor instanceof Closure) {
                    throw new Exception("Invalid processor");
                }
                $res = $processor($baseName, $path);
                if ($res === true) {
                    return $baseName;
                }
                return $res;
            };
        } else {
            $processor = function ($path) {
                return basename($path);
            };
        }
        return self::paths($dirPath, $processor, $conf);
    }

    public static function filePathsWithExt(
        string|iterable $dirPath,
        array $extensions,
        array|bool|null $conf = null
    ): Generator {
        $conf = self::normalizeConf($conf);
        foreach ($extensions as $k => $extension) {
            $extensions[$k] = preg_quote($extension, '/');
        }
        return self::filePaths($dirPath, '/\.(' . implode('|', $extensions) . ')$/si', $conf);
    }

    /**
     * Shortcut for the paths() with $conf['type'] == Stat::FILE option.
     */
    public static function filePaths(
        string|iterable $dirPath,
        string|null|callable $processor = null,
        array|bool|null $conf = null
    ): Generator {
        $conf = self::normalizeConf($conf);
        $conf['type'] = Stat::FILE;
        return self::paths($dirPath, $processor, $conf);
    }

    public static function fileNames(
        string|iterable $dirPath,
        string|null|callable $processor = null,
        array|bool|null $conf = null
    ): Generator {
        $conf = self::normalizeConf($conf);
        $conf['type'] = Stat::FILE;
        return self::names($dirPath, $processor, $conf);
    }

    public static function brokenLinkPaths(string|iterable $dirPath): Generator {
        return Dir::linkPaths($dirPath, [Link::class, 'isBroken']);
    }

    public static function linkPaths(string|iterable $dirPath, callable $filter): Generator {
        foreach (Dir::paths($dirPath) as $path) {
            if (is_link($path)) {
                if ($filter) {
                    if ($filter($path)) {
                        yield $path;
                    }
                } else {
                    yield $path;
                }
            }
        }
    }

    /**
     * @param string $relDirPath
     * @param int $mode
     * @return Path to the created directory.
     */
    public static function createTmp(string $relDirPath, int $mode = Stat::DIR_MODE): string {
        return self::create(
            Path::combine(Env::tmpDirPath(), $relDirPath),
            $mode
        );
    }

    public static function deleteIfExists(string|iterable $dirPath, bool|callable $predicate = true): void {
        if (is_iterable($dirPath)) {
            foreach ($dirPath as $path) {
                if (is_dir($path)) {
                    self::_delete($path, $predicate);
                }
            }
        } else {
            if (is_dir($dirPath)) {
                self::_delete($dirPath, $predicate);
            }
        }
    }

    public static function deleteEmptyDirs(string|iterable $dirPath, callable $predicate = null): void {
        foreach (self::emptyDirPaths($dirPath, $predicate) as $dPath) {
            self::delete($dPath);
        }
    }

    public static function emptyDirPaths(string|iterable $dirPath, callable $predicate = null): iterable {
        if (is_string($dirPath)) {
            $dirPath = [$dirPath];
        }
        foreach ($dirPath as $dPath) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $fileInfo) {
                $path = $fileInfo->getPathname();
                if (is_dir($path) && self::isEmpty($path)) {
                    if ($predicate && !$predicate($path)) {
                        continue;
                    }
                    yield $path;
                }
            }
        }
    }

    public static function isEmpty(string|iterable $dirPath): bool {
        foreach (self::paths($dirPath, null, ['recursive' => false]) as $_) {
            return false;
        }
        return true;
    }

    /**
     * @param string|array $dirPath
     * @param int $mode
     * @param bool $recursive
     * @return string|array string if $dirPath is a string, an array if the $dirPath is an array
     * @TODO: Accept iterable
     */
    public static function recreate($dirPath, int $mode = Stat::DIR_MODE, bool $recursive = true) {
        if (is_array($dirPath)) {
            $res = [];
            foreach ($dirPath as $key => $path) {
                $res[$key] = self::recreate($path, $mode, $recursive);
            }
            return $res;
        } elseif (!is_string($dirPath)) {
            throw new Exception('Invalid type of the argument');
        }
        if (is_dir($dirPath)) {
            self::delete($dirPath);
        }
        self::create($dirPath, $mode, $recursive);

        return $dirPath;
    }

    /**
     * @param string $otherDirPath
     * @param callable $fn
     * @return mixed
     */
    public static function in(string $otherDirPath, callable $fn): mixed {
        $curDirPath = getcwd();
        try {
            chdir($otherDirPath);
            $res = $fn($otherDirPath);
        } finally {
            chdir($curDirPath);
        }
        return $res;
    }

    /**
     * Returns number of entries in the given directory.
     * @param string $dirPath
     * @return int
     */
    public static function numOfEntries(string $dirPath): int {
        return iterator_count(static::paths($dirPath));
    }
}
