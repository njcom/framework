<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Fs;

use DirectoryIterator;
use FilesystemIterator;
use Morpho\Base\Conf;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class Dir {
    public static function move(string $sourceDirPath, string $targetDirPath): string {
        // @TODO: why not rename()?
        self::copy($sourceDirPath, $targetDirPath);
        self::delete($sourceDirPath);
        return $targetDirPath;
    }

    public static function copy(string $sourceDirPath, string $targetDirPath, array $conf = null): string {
        // @TODO: Handle dots and relative paths: '..', '.'
        // @TODO: Handle the case: cp module/system ../../dst/module should create ../../dst/module/system
        self::mustExist($sourceDirPath);

        if ($sourceDirPath === $targetDirPath) {
            throw new Exception("Cannot copy the directory '$sourceDirPath' into itself");
        }

        $conf = Conf::check(
            [
                'overwrite'    => false,
                'skipIfExists' => false,
            ],
            (array)$conf
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

        foreach (self::paths($sourceDirPath) as $path) {
            $targetPath = $targetDirPath . '/' . basename($path);
            if (is_file($path) || is_link($path)) {
                File::copy($path, $targetPath, $conf['overwrite'], $conf['skipIfExists']);
            } else {
                self::copy($path, $targetPath, $conf);
            }
        }

        return $targetDirPath;
    }

    public static function copyContents(string $sourceDirPath, string $targetDirPath): string {
        foreach (new DirectoryIterator($sourceDirPath) as $item) {
            if ($item->isDot()) {
                continue;
            }
            $entryPath = $item->getPathname();
            $relPath = Path::rel($sourceDirPath, $entryPath);
            Entry::copy($entryPath, $targetDirPath . '/' . $relPath);
        }
        return $targetDirPath;
    }

    public static function create(string|array $dirPath, int $perms = null, bool $recursive = true): string|array {
        if (is_array($dirPath)) {
            foreach ($dirPath as $path) {
                self::recreate($path, $perms, $recursive);
            }
            return $dirPath;
        }
        if (null === $perms) {
            $perms = Stat::DIR_PERMS;
        }
        if ('' === $dirPath) {
            throw new Exception("The directory path is empty");
        }
        if (is_dir($dirPath)) {
            return $dirPath;
        }
        if (!mkdir($dirPath, $perms, $recursive)) {
            throw new RuntimeException("Unable to create the directory '$dirPath' with permissions: $perms");
        }
        return $dirPath;
    }

    public static function recreate(string|array $dirPath, int $perms = null, bool $recursive = true): string|array {
        if (is_array($dirPath)) {
            foreach ($dirPath as $path) {
                self::recreate($path, $perms, $recursive);
            }
            return $dirPath;
        }
        if (null === $perms) {
            $perms = Stat::DIR_PERMS;
        }
        if (is_dir($dirPath)) {
            self::delete($dirPath);
        }
        self::create($dirPath, $perms, $recursive);
        return $dirPath;
    }

    public static function delete(string $dirPath, bool|callable $selector = null): void {
        static::_delete($dirPath, $selector);
    }

    public static function deleteEmptyDirs(string $dirPath, bool $recursive = false): void {
        foreach (self::emptyDirPaths($dirPath, $recursive, RecursiveIteratorIterator::CHILD_FIRST) as $emptyDirPath) {
            self::delete($emptyDirPath);
        }
    }

    public static function emptyDirPaths(string $dirPath, bool $recursive = false, int $flags = null): iterable {
        foreach (self::dirPaths($dirPath, $recursive, $flags) as $maybeEmptyDirPath) {
            if (self::isEmpty($maybeEmptyDirPath)) {
                yield $maybeEmptyDirPath;
            }
        }
    }

    /**
     * @param string $dirPath
     * @param bool   $recursive
     * @param ?int   $flags
     * @return \RecursiveIteratorIterator<string, \SplFileInfo>
     */
    public static function it(string $dirPath, bool $recursive = false, int $flags = null): RecursiveIteratorIterator {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath, FilesystemIterator::UNIX_PATHS),
            $flags ?: RecursiveIteratorIterator::SELF_FIRST,
        );
        if (!$recursive) {
            $it->setMaxDepth(0);
        }
        //return new DirectoryIterator($dirPath);
        return $it;
    }

    public static function paths(string $dirPath, bool $recursive = false, int $flags = null): iterable {
        foreach (self::it($dirPath, $recursive, $flags) as $entry) {
            $fileName = $entry->getFileName();
            if ($fileName == '.' || $fileName == '..') {
                continue;
            }
            yield $entry->getPathname();
        }
    }

    public static function dirPaths(string $dirPath, bool $recursive = false, int $flags = null): iterable {
        foreach (self::paths($dirPath, $recursive, $flags) as $path) {
            if (is_dir($path) && !is_link($path)) {
                yield $path;
            }
        }
    }

    public static function filePaths(string $dirPath, bool $recursive = false): iterable {
        foreach (self::paths($dirPath, $recursive/*, $flags*/) as $path) {
            if (is_file($path)) {
                yield $path;
            }
        }
    }

    public static function linkPaths(string $dirPath, bool $recursive = true): iterable {
        foreach (Dir::it($dirPath, $recursive) as $path) {
            if ($path->isLink()) {
                yield $path->getPathname();
            }
        }
    }

    public static function brokenLinkPaths(string $dirPath): iterable {
        foreach (self::linkPaths($dirPath) as $linkPath) {
            if (Link::isBroken($linkPath)) {
                yield $linkPath;
            }
        }
    }

    public static function dirNames(string $dirPath, bool $recursive = false): iterable {
        foreach (self::dirPaths($dirPath, $recursive) as $dirPath) {
            yield basename($dirPath);
        }
    }

    public static function fileNames(string $dirPath, bool $recursive = false): iterable {
        foreach (self::filePaths($dirPath, $recursive) as $filePath) {
            yield basename($filePath);
        }
    }

/*    public static function names(string|iterable $dirPath, bool $recursive): iterable {
    }*/

    public static function isEmpty(string $dirPath): bool {
        foreach (self::paths($dirPath) as $_) {
            return false;
        }
        return true;
    }

    public static function in(string $cdDirPath, callable $fn): mixed {
        $curDirPath = getcwd();
        try {
            chdir($cdDirPath);
            $res = $fn($cdDirPath);
        } finally {
            chdir($curDirPath);
        }
        return $res;
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

    /*
    public static function filePathsWithExt(string|iterable $dirPath, array $extensions, array|bool|null $conf = null): iterable {
        $conf = self::normalizeConf($conf);
        foreach ($extensions as $k => $extension) {
            $extensions[$k] = preg_quote($extension, '/');
        }
        return self::filePaths($dirPath, '/\.(' . implode('|', $extensions) . ')$/si', $conf);
    }

    public static function createTmp(string $relDirPath, int $perms = Stat::DIR_PERMS): string {
        return self::create(
            Path::combine(Env::tmpDirPath(), $relDirPath),
            $perms
        );
    }

    public static function numOfEntries(string $dirPath): int {
        throw new NotImplementedException();

        return iterator_count(static::paths($dirPath));
    }
    */

    private static function _delete(string $dirPath, callable|bool|null $selectorOrDeleteSelf): void {
        if (null === $selectorOrDeleteSelf || is_callable($selectorOrDeleteSelf)) {
            self::__delete($dirPath, $selectorOrDeleteSelf);
        } elseif (is_bool($selectorOrDeleteSelf)) {
            if ($selectorOrDeleteSelf) {
                // Delete self
                $selector = null;
            } else {
                // Not delete self
                $selector = function ($path, $isDir) use ($dirPath) {
                    return $path !== $dirPath;
                };
            }
            self::__delete($dirPath, $selector);
        }
    }

    private static function __delete(string $dirPath, callable $selector = null): void {
        if (!file_exists($dirPath)) {
            return;
        }
        $absPath = Path::normalize($dirPath);
        foreach (new DirectoryIterator($absPath) as $entry) {
            if ($entry->isDot()) {
                continue;
            }
            if ($entry->isLink()) {
                $entryPath = $entry->getPathname();
                self::removeFile($entryPath);
                continue;
            }
            $entryPath = $entry->getPathname();
            if ($entry->isDir()) {
                if (null !== $selector) {
                    if ($selector($entryPath, true)) {
                        // If it is a directory and we need to delete this directory, delete contents regardless of the $selector, so pass the `null` as the second argument.
                        self::__delete($entryPath);
                    } else {
                        // The $selector can be used for the directory contents, so pass it as the argument.
                        self::__delete($entryPath, $selector);
                    }
                } else {
                    self::__delete($entryPath);
                }
            } else {
                if (null === $selector || $selector($entryPath, false)) {
                    self::removeFile($entryPath);
                }
            }
        }
        if (null === $selector || $selector($absPath, true)) {
            self::removeDir($absPath);
        }
    }

    private static function removeFile(string $filePath): void {
        if (!unlink($filePath)) {
            throw new RuntimeException("The file '$filePath' can't be deleted, check permissions");
        }
        clearstatcache(true, $filePath);
    }

    private static function removeDir(string $dirPath): void {
        if (!rmdir($dirPath)) {
            throw new RuntimeException(
                "Unable to delete the directory '$dirPath': it may be not empty or doesn't have relevant permissions"
            );
        }
        clearstatcache(true, $dirPath);
    }
}
