<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Testing;

use Morpho\Fs\Stat;
use RuntimeException;

use function min;
use function strlen;
use function substr;
use function umask;

class VfsFile extends VfsEntry {
    private VfsFileOpenMode $openMode;

    private string $contents = '';

    private int $offset = 0;

    public function open(VfsFileOpenMode $openMode): void {
        $this->openMode = $openMode;
        if ($openMode->truncate()) {
            $this->contents = '';
            $this->offset = 0;
        } elseif ($openMode->append()) {
            $this->offset = strlen($this->contents);
        } else {
            $this->offset = 0;
        }
        $this->isOpen = true;
    }

    public function openMode(): VfsFileOpenMode {
        return $this->openMode;
    }

    public function write(string $contents): int {
        $this->checkIsOpen();
        if ($this->openMode->readOnly()) {
            throw new RuntimeException('File isOpen for reading only');
        }
        if ($this->openMode->append()) {
            $this->contents .= $contents;
        } else {
            $this->contents = $contents;
        }
        return strlen($contents);
    }

    public function read(int $n): string {
        $this->checkIsOpen();
        $n = min($n, strlen($this->contents) - $this->offset);
        if (!$n) {
            $contents = '';
        } else {
            $contents = substr($this->contents, $this->offset, $n);
        }
        $this->offset += $n;
        return $contents;
    }

    public function seek(int $offset, int $whence = SEEK_SET): bool {
        $this->checkIsOpen();
        if ($whence === SEEK_CUR) {
            $offset += $this->offset;
        } elseif ($whence === SEEK_END) {
            $offset = strlen($this->contents) + $offset;
        }
        if ($offset <= strlen($this->contents)) {
            $this->offset = $offset;
            return true;
        }
        return false;
    }

    public function offset(): int {
        $this->checkIsOpen();
        return $this->offset;
    }

    public function truncate(int $newSize): void {
        $this->contents = substr($this->contents, 0, $newSize);
    }

    public function eof(): bool {
        $this->checkIsOpen();
        return $this->offset >= strlen($this->contents);
    }

    public function stat(): VfsEntryStat {
        $stat = parent::stat();
        $stat['size'] = $this->count();
        return $stat;
    }

    public function count(): int {
        return strlen($this->contents);
    }

    protected function normalizeStat(VfsEntryStat $stat): void {
        parent::normalizeStat($stat);
        if (!isset($stat['mode'])) {
            $typeBits = Stat::FILE;
            $permissionBits = Stat::FILE_BASE_MODE & ~umask();
            $stat['mode'] = $typeBits | $permissionBits;
        }
    }
}
