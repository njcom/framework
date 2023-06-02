<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Caching;

use FilesystemIterator;
use InvalidArgumentException;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function bin2hex;
use function chmod;
use function defined;
use function disk_free_space;
use function file_exists;
use function file_put_contents;
use function hash;
use function is_dir;
use function is_writable;
use function mkdir;
use function pathinfo;
use function realpath;
use function rename;
use function rmdir;
use function sprintf;
use function strlen;
use function strrpos;
use function substr;
use function tempnam;
use function unlink;

/**
 * This class based on \Doctrine\Common\Cache\FileCache from Doctrine project (MIT license).
 * For more information, see <http://www.doctrine-project.org>.
 * Copyright (c) 2006-2015 Doctrine Project
 */
abstract class FileCache extends Cache {
    /**
     * The cache directory.
     */
    protected string $dirPath;

    /**
     * The cache file extension.
     */
    private string $extension;

    private int $umask;

    private int $dirPathStrLength;

    private int $extensionStrLength;

    /**
     * @var bool
     */
    private bool $isRunningOnWindows;

    public function __construct(string $dirPath, string $extension, int $umask = 0002) {
        $this->umask = $umask;

        if (!$this->createDirIfNeeded($dirPath)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The directory "%s" does not exist and could not be created.',
                    $dirPath
                )
            );
        }

        if (!is_writable($dirPath)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The directory "%s" is not writable.',
                    $dirPath
                )
            );
        }

        // YES, this needs to be *after* createPathIfNeeded()
        $this->dirPath = realpath($dirPath);
        $this->extension = $extension;

        $this->dirPathStrLength = strlen($this->dirPath);
        $this->extensionStrLength = strlen($this->extension);
        $this->isRunningOnWindows = defined('PHP_WINDOWS_VERSION_BUILD');
    }

    /**
     * Create path if needed.
     *
     * @param string $path
     * @return bool true on success or if path already exists, false if path cannot be created.
     */
    private function createDirIfNeeded(string $path): bool {
        if (!is_dir($path)) {
            if (false === @mkdir($path, 0777 & (~$this->umask), true) && !is_dir($path)) {
                return false;
            }
        }
        return true;
    }

    public function delete(string $key): bool {
        $filePath = $this->cacheFilePath($key);
        return @unlink($filePath) || !file_exists($filePath);
    }

    protected function cacheFilePath(string $key): string {
        $hash = hash('sha256', $key);

        // This ensures that the filename is unique and that there are no invalid chars in it.
        if ('' === $key
            || ((strlen($key) * 2 + $this->extensionStrLength) > 255)
            || ($this->isRunningOnWindows && ($this->dirPathStrLength + 4 + strlen(
                        $key
                    ) * 2 + $this->extensionStrLength) > 258)
        ) {
            // Most filesystems have a limit of 255 chars for each path component. On Windows the the whole path is limited
            // to 260 chars (including terminating null char). Using long UNC ("\\?\" prefix) does not work with the PHP API.
            // And there is a bug in PHP (https://bugs.php.net/bug.php?id=70943) with path lengths of 259.
            // So if the id in hex representation would surpass the limit, we use the hash instead. The prefix prevents
            // collisions between the hash and bin2hex.
            $filename = '_' . $hash;
        } else {
            $filename = bin2hex($key);
        }

        return $this->dirPath
            . DIRECTORY_SEPARATOR
            . substr($hash, 0, 2)
            . DIRECTORY_SEPARATOR
            . $filename
            . $this->extension;
    }

    public function clear(): bool {
        foreach ($this->dirIt() as $name => $file) {
            if ($file->isDir()) {
                // Remove the intermediate directories which have been created to balance the tree. It only takes effect
                // if the directory is empty. If several caches share the same directory but with different file extensions,
                // the other ones are not removed.
                @rmdir($name);
            } elseif ($this->isFilenameEndingWithExtension($name)) {
                // If an extension is set, only remove files which end with the given extension.
                // If no extension is set, we have no other choice than removing everything.
                @unlink($name);
            }
        }

        return true;
    }

    /**
     * @return Iterator
     */
    private function dirIt(): Iterator {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->dirPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
    }

    private function isFilenameEndingWithExtension(string $filePath): bool {
        return '' === $this->extension
            || strrpos($filePath, $this->extension) === (strlen($filePath) - $this->extensionStrLength);
    }

    public function stats(): ?array {
        $usage = 0;
        foreach ($this->dirIt() as $name => $file) {
            if (!$file->isDir() && $this->isFilenameEndingWithExtension($name)) {
                $usage += $file->getSize();
            }
        }

        $free = disk_free_space($this->dirPath);

        return [
            Cache::STATS_HITS             => null,
            Cache::STATS_MISSES           => null,
            Cache::STATS_UPTIME           => null,
            Cache::STATS_MEMORY_USAGE     => $usage,
            Cache::STATS_MEMORY_AVAILABLE => $free,
        ];
    }

    /**
     * Writes a string content to file in an atomic way.
     *
     * @param string $filename Path to the file where to write the data.
     * @param string $content The content to write
     *
     * @return bool true on success, false if path cannot be created, if path is not writable or an any other error.
     */
    protected function writeFile(string $cacheFilePath, string $content): bool {
        $dirPath = pathinfo($cacheFilePath, PATHINFO_DIRNAME);
        if (!$this->createDirIfNeeded($dirPath)) {
            return false;
        }
        if (!is_writable($dirPath)) {
            return false;
        }
        $tmpFilePath = tempnam($dirPath, 'swap');
        @chmod($tmpFilePath, 0666 & (~$this->umask));

        if (file_put_contents($tmpFilePath, $content) !== false) {
            @chmod($tmpFilePath, 0666 & (~$this->umask));
            if (@rename($tmpFilePath, $cacheFilePath)) {
                return true;
            }
            @unlink($tmpFilePath);
        }
        return false;
    }
}
