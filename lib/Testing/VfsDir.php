<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Testing;

use Countable;
use Iterator;
use LogicException;
use Morpho\Base\NotImplementedException;
use Morpho\Fs\Stat;

use RuntimeException;

use function count;
use function current;
use function key;
use function next;
use function prev;
use function reset;
use function umask;

class VfsDir extends VfsEntry implements Iterator, Countable {
    //private $index = 0;

    private array $entries = [];

    public function open(): void {
        $this->rewind();
        $this->isOpen = true;
    }

    public function rewind(): void {
        reset($this->entries);
    }

    public function close(): void {
        parent::close();
        //$this->index = 0;
    }

    public function entry(string $name): IVfsEntry {
        if (!isset($this->entries[$name])) {
            throw new RuntimeException('Entry not found');
        }
        return $this->entries[$name];
    }

    /*public function entryExists(string $name): bool {
        return isset($this->entries[$name]);
    }*/

    /**
     * @return IVfsEntry|false
     */
    public function entryOrNone(string $name): IVfsEntry|bool {
        return $this->entries[$name] ?? false;
    }

    public function dirExists(string $name): bool {
        return isset($this->entries[$name]) && $this->entries[$name] instanceof VfsDir;
    }

    public function dir(string $name): VfsDir {
        return $this->entries[$name];
    }

    public function createDir(string $uri, VfsEntryStat $stat): VfsDir {
        $this->normalizeStat($stat);
        $dir = new VfsDir($uri, $stat);
        return $this->entries[$dir->name()] = $dir;
    }

    protected function normalizeStat(VfsEntryStat $stat): void {
        parent::normalizeStat($stat);
        if (!isset($stat['mode'])) {
            $typeBits = Stat::DIR;
            $permissionBits = Stat::DIR_BASE_MODE & ~umask();
            $stat['mode'] = $typeBits | $permissionBits;
        }
    }

    public function deleteDir(string $name): void {
        if (!isset($this->entries[$name])) {
            throw new RuntimeException('Directory not found');
        }
        $dir = $this->entries[$name];
        if (!$dir instanceof VfsDir) {
            throw new LogicException();
        }
        //$dir->close();
        unset($this->entries[$name]);
    }

    /**
     * @return VfsDir|false
     */
    public function dirOrNone(string $name): bool|VfsDir {
        $dir = $this->entries[$name] ?? false;
        if ($dir && !$dir instanceof VfsDir) {
            throw new LogicException();
        }
        return $dir;
    }

    public function file(string $name): VfsFile {
        return $this->entries[$name];
    }

    /**
     * @return VfsFile|false
     */
    public function fileOrNone(string $name): bool|VfsFile {
        $file = $this->entries[$name] ?? false;
        if ($file && !$file instanceof VfsFile) {
            throw new LogicException();
        }
        return $file;
    }

    public function createFile(string $uri, VfsEntryStat $stat): VfsFile {
        $this->normalizeStat($stat);
        $file = new VfsFile($uri, $stat);
        return $this->entries[$file->name()] = $file;
    }

    public function deleteFile(string $name): void {
        if (!isset($this->entries[$name])) {
            throw new RuntimeException('File not found');
        }
        $file = $this->entries[$name];
        if (!$file instanceof VfsFile) {
            throw new LogicException();
        }
        //$file->close();
        unset($this->entries[$name]);
    }

    public function unregisterEntry(string $name): IVfsEntry {
        $entry = $this->entries[$name];
        unset($this->entries);
        return $entry;
    }

    public function registerEntry(IVfsEntry $entry): void {
        $this->entries[$entry->name()] = $entry;
    }

    public function current(): mixed {
        return current($this->entries);
    }

    public function next(): void {
        next($this->entries);
    }

    public function key(): mixed {
        throw new NotImplementedException();
    }

    public function valid(): bool {
        next($this->entries);
        $key = key($this->entries);
        $valid = $key !== null;
        prev($this->entries);
        return $valid;
    }

    public function count(): int {
        return count($this->entries);
    }
}
