<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Testing;

use Morpho\Base\NotImplementedException;
use Morpho\Fs\IFs;
use Morpho\Fs\Stat;
use RuntimeException;
use UnexpectedValueException;

use function basename;
use function boolval;
use function clearstatcache;
use function dirname;
use function in_array;
use function preg_match;
use function stream_get_wrappers;
use function stream_wrapper_register;
use function stream_wrapper_unregister;
use function strlen;
use function substr;
use function time;
use function umask;

/**
 * This implementation of VFS was written from scratch, with help of sources found in the package mikey179/vfsStream (Copyright (c) 2007-2015, Frank Kleine), which were used to find answers on some questions.
 */
class Vfs implements IFs {
    public const SCHEME = 'vfs';
    public const URI_PREFIX = self::SCHEME . '://';
    private static ?VfsDir $root = null;
    private ?VfsDir $dir = null;
    private ?VfsFile $file = null;

    public static function register(): void {
        stream_wrapper_register(self::SCHEME, __CLASS__);
    }

    public static function isRegistered(): bool {
        return in_array(self::SCHEME, stream_get_wrappers());
    }

    public static function unregister(): void {
        self::resetState();
        stream_wrapper_unregister(self::SCHEME);
    }

    public static function resetState(): void {
        self::$root = null;
    }

    public function __destruct() {
        $this->stream_close();
        $this->dir_closedir();
    }

    // ------------------------------------------------------------------------
    // IFs interface

    public function stream_close(): void {
        if ($this->file) {
            if ($this->file->isOpen()) {
                $this->file->close();
            }
        }
    }

    public function dir_closedir(): bool {
        if ($this->dir) {
            if ($this->dir->isOpen()) {
                $this->dir->close();
            }
        }
        return true;
    }

    /**
     * @param string $uri
     * @param string $mode
     * @param int $flags
     * @param null|string $openedUri
     * @return bool
     */
    public function stream_open(string $uri, string $mode, int $flags, ?string &$openedUri): bool {
        if (null !== $openedUri) {
            throw new NotImplementedException();
        }
        if (0 !== $flags) {
            // @TODO
            /*            if ($flags | STREAM_REPORT_ERRORS) {
                            d($flags);
                            trigger_error(), see http://php.net/manual/en/streamwrapper.stream-open.php
                        }*/
        }
        $this->checkUri($uri);
        $parentDir = $this->parentDir($uri);
        if ($parentDir->dirExists(self::entryName($uri))) {
            throw new RuntimeException('Unable to open file, entry is a directory');
        }
        $openMode = new VfsFileOpenMode($mode);
        if ($openMode->create()) {
            $file = $parentDir->createFile(
                $uri,
                new VfsEntryStat(
                    [
                        'mode' => $this->fileMode(),
                    ]
                )
            );
        } else {
            $file = $parentDir->file(self::entryName($uri));
        }
        $file->open($openMode);
        $this->file = $file;
        return true;
    }

    protected function checkUri(string $uri): void {
        if (!str_starts_with($uri, self::URI_PREFIX)) {
            throw new RuntimeException('Invalid URI');
        }
        if (preg_match('~^(' . self::SCHEME . '://[^/]|://$)~si', $uri)) {
            throw new RuntimeException('Relative URIs are not supported');
        }
    }

    private function parentDir(string $uri): VfsDir {
        return $this->root()->dirByUri(self::parentDirUri($uri));
    }

    protected function root(): VfsRoot {
        if (null === self::$root) {
            self::$root = new VfsRoot(self::URI_PREFIX . '/', new VfsEntryStat(['mode' => $this->dirMode()]));
        }
        return self::$root;
    }

    private function dirMode(int $mode = Stat::DIR_BASE_PERMS): int {
        return ($mode & ~umask()) | Stat::DIR;
    }

    public static function parentDirUri(string $uri): string {
        $prefix = self::URI_PREFIX;
        return $prefix . dirname(substr($uri, strlen($prefix)));
    }

    public static function entryName(string $uri): string {
        if ($uri === '') {
            throw new UnexpectedValueException('Empty URI');
        }
        $uriNoPrefix = self::stripUriPrefix($uri);
        if ($uriNoPrefix === '' || $uriNoPrefix[0] !== '/') {
            throw new UnexpectedValueException("Path must be not empty and must start with the '/'");
        }
        if ($uriNoPrefix === '/') {
            throw new UnexpectedValueException('Unable to get name for the root');
        }
        return basename($uriNoPrefix);
    }

    public static function stripUriPrefix(string $uri): string {
        $prefix = self::prefixUri();
        if (!str_starts_with($uri, $prefix)) {
            throw new UnexpectedValueException();
        }
        $uri = substr($uri, strlen($prefix));
        return $uri;
    }

    public static function prefixUri(string $uri = ''): string {
        return self::URI_PREFIX . $uri;
    }

    private function fileMode(int $mode = Stat::FILE_BASE_PERMS): int {
        return ($mode & ~umask()) | Stat::FILE;
    }

    public function stream_lock(int $operation): bool {
        throw new NotImplementedException();
        //d(func_get_args());
    }

    public function stream_read(int $count): string {
        return $this->file->read($count);
    }

    public function stream_write(string $contents): int {
        return $this->file->write($contents);
    }

    public function stream_eof(): bool {
        return $this->file->eof();
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool {
        return $this->file->seek($offset, $whence);
    }

    public function stream_tell(): int {
        return $this->file->offset();
    }

    public function stream_flush(): bool {
        return true;
    }

    public function stream_truncate(int $newSize): bool {
        $this->file->truncate($newSize);
        clearstatcache(true, $this->file->uri());
        return true;
    }

    public function stream_stat(): array {
        /*        if (!$this->file) {
                    return [];
                }*/
        return $this->file->stat()->getArrayCopy();
    }

    public function stream_metadata(string $uri, int $option, mixed $args): bool {
        switch ($option) {
            case STREAM_META_TOUCH: // (The method was called in response to touch())
                if (!isset($args[0])) { // touch time/mtime
                    $args[0] = time();
                }
                if (!isset($args[1])) { // access time/atime
                    $args[1] = $args[0];
                }
                $file = $this->root()->fileByUriOrNone($uri);
                if (!$file) {
                    $this->parentDir($uri)->createFile($uri, new VfsEntryStat(['mode' => $this->fileMode()]));
                }
                break;
            case STREAM_META_OWNER_NAME: // (The method was called in response to chown() with string parameter)
                throw new NotImplementedException();
                break;
            case STREAM_META_OWNER: // (The method was called in response to chown())
                // STREAM_META_OWNER_NAME or STREAM_META_GROUP_NAME: The name of the owner user/group as string.
                throw new NotImplementedException();
                break;
            case STREAM_META_GROUP_NAME: // (The method was called in response to chgrp())
                // STREAM_META_OWNER_NAME or STREAM_META_GROUP_NAME: The name of the owner user/group as string.
                throw new NotImplementedException();
                break;
            case STREAM_META_GROUP: // (The method was called in response to chgrp())
                throw new NotImplementedException();
                break;
            case STREAM_META_ACCESS: // (The method was called in response to chmod())
                $entry = $this->root()->entryByUri($uri);
                // @TODO: Check how `chmod(2)` is implemented and do the same. Check mode and owner.
                $entry->chmod($args);
                break;
            default:
                throw new UnexpectedValueException();
        }
        return true;
    }

    /**
     * @return resource
     */
    public function stream_cast(int $castAs) {
        throw new NotImplementedException();
        //d(func_get_args());
    }

    public function stream_set_option(int $option, int $arg1, int $arg2): bool {
        throw new NotImplementedException();
        //d(func_get_args());
    }

    public function unlink(string $uri): bool {
        $parentDir = $this->parentDir($uri);
        $parentDir->deleteFile(self::entryName($uri));
        return true;
    }

    public function rename(string $oldEntryUri, string $newEntryUri): bool {
        $entry = $this->parentDir($oldEntryUri)->unregisterEntry(self::entryName($oldEntryUri));
        $entry->setUri($newEntryUri);
        $this->parentDir($newEntryUri)->registerEntry($entry);
        clearstatcache(true, $oldEntryUri);
        clearstatcache(true, $newEntryUri);
        return true;
    }

    public function mkdir(string $uri, int $mode, int $flags): bool {
        $parentDirUri = self::parentDirUri($uri);
        $parentDir = $this->root()->dirByUriOrNone($parentDirUri);
        if ($parentDir && $parentDir->dirExists(self::entryName($uri))) {
            throw new RuntimeException('Unable to create directory, such directory already exists');
        }
        $recursive = boolval($flags & STREAM_MKDIR_RECURSIVE);
        $stat = new VfsEntryStat(
            [
                'mode' => $this->dirMode($mode),
            ]
        );
        if ($recursive) {
            $this->root()->createAllDirs($uri, $stat);
            clearstatcache();
        } else {
            $parentDir->createDir($uri, $stat);
            clearstatcache(true, $uri);
        }
        return true;
    }

    public function rmdir(string $uri, int $flags): bool {
        $recursive = boolval($flags & STREAM_MKDIR_RECURSIVE);
        if ($recursive) {
            throw new NotImplementedException();
        }
        $parentDir = $this->parentDir($uri);
        $parentDir->deleteDir(self::entryName($uri));
        clearstatcache(true, $uri);
        return true;
    }

    /**
     * @return array|false
     */
    public function url_stat(string $uri, int $flags) {
        $entry = $this->root()->entryByUriOrNone($uri);
        if (!$entry) {
            return false;
        }
        return $entry->stat()->getArrayCopy();
    }

    public function dir_opendir(string $uri, int $flags): bool {
        $dir = $this->dir = $this->root()->dirByUri($uri);
        $dir->open();
        return true;
    }

    /**
     * @return string|false Returns false if there is no next file.
     */
    public function dir_readdir(): string|bool {
        if ($this->dir) {
            $dir = $this->dir;
            $current = $dir->current();
            if (!$current) {
                return false;
            }
            $entry = $current->name();
            $dir->next();
            return $entry;
        }
        return false;
    }

    public function dir_rewinddir(): bool {
        if ($this->dir) {
            $this->dir->rewind();
        }
        return true;
    }
}
