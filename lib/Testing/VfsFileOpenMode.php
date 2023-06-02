<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Testing;

use UnexpectedValueException;

use function in_array;
use function str_replace;
use function strchr;

class VfsFileOpenMode {
    private const O_RDONLY = 00000000;

    // Taken from https://github.com/torvalds/linux/blob/master/include/uapi/asm-generic/fcntl.h
    private const O_WRONLY = 00000001;
    private const O_RDWR = 00000002;
    private const O_CREAT = 00000100;
    private const O_APPEND = 00002000;
    private const O_TRUNC = 00001000;
    /*    private const O_CLOEXEC  = 02000000;
        private const O_EXCL     = 00000200;
        private const O_NONBLOCK = 00004000;*/
    private $mode;

    public function __construct(string $mode) {
        $this->mode = $this->parseMode($mode);
    }

    protected function parseMode(string $mode): int {
        // On *nix [there is difference between text and binary mode](https://stackoverflow.com/questions/2266992/no-o-binary-and-o-text-flags-in-linux)
        $mode = str_replace('b', '', $mode);
        $supported = ['r', 'r+', 'w', 'w+', 'a', 'a+'];
        if (!in_array($mode, $supported, true)) {
            throw new UnexpectedValueException();
        }
        // Taken from PHP sources, main/streams/plain_wrapper.c file
        switch ($mode[0]) {
            case 'r':
                $flags = self::O_RDONLY;
                break;
            case 'w':
                $flags = self::O_TRUNC | self::O_CREAT;
                break;
            case 'a':
                $flags = self::O_CREAT | self::O_APPEND;
                break;
            /*            case 'x':
                            $flags = self::O_CREAT | self::O_EXCL;
                            break;
                        case 'c':
                            $flags = self::O_CREAT;
/*                            break;*/
            default:
                throw new UnexpectedValueException();
        }

        if (strchr($mode, '+')) {
            $flags |= self::O_RDWR;
        } else {
            if ($flags) {
                $flags |= self::O_WRONLY;
            }
        }
        /*else {
            $flags |= self::O_RDONLY;
        }
        */

        /*
        if (strchr($mode, 'e')) {
            $flags |= self::O_CLOEXEC;
        }

        if (strchr($mode, 'n')) {
            $flags |= self::O_NONBLOCK;
        }
        */
        /*
                if (strchr($mode, 't')) {
                    $flags |= self::O_TEXT;
                } else {
                    $flags |= self::O_BINARY;
                }*/

        return $flags;
    }

    public function create(): bool {
        return (bool) ($this->mode & self::O_CREAT);
    }

    public function truncate(): bool {
        return (bool) ($this->mode & self::O_TRUNC);
    }

    public function canWrite(): bool {
        return $this->append() || $this->writeOnly() || $this->readWrite();
    }

    public function append(): bool {
        return (bool) ($this->mode & self::O_APPEND);
    }

    public function writeOnly(): bool {
        return (bool) ($this->mode & self::O_WRONLY);
    }

    public function readWrite(): bool {
        return (bool) ($this->mode & self::O_RDWR);
    }

    public function canRead(): bool {
        return $this->readOnly() || $this->readWrite();
    }

    public function readOnly(): bool {
        return $this->mode === self::O_RDONLY;
    }
}
