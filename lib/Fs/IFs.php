<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Fs;

/**
 * @see http://www.php.net/manual/class.streamwrapper.php
 */
interface IFs {
    public function stream_open(string $uri, string $mode, int $flags, ?string &$openedUri): bool;

    public function stream_close(): void;

    public function stream_lock(int $operation): bool;

    public function stream_read(int $count): string;

    public function stream_write(string $contents): int;

    public function stream_eof(): bool;

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool;

    public function stream_tell(): int;

    public function stream_flush(): bool;

    public function stream_truncate(int $newSize): bool;

    public function stream_stat(): array;

    public function stream_metadata(string $uri, int $option, mixed $args): bool;

    /**
     * @return resource
     */
    public function stream_cast(int $castAs);

    public function stream_set_option(int $option, int $arg1, int $arg2): bool;

    public function unlink(string $uri): bool;

    public function rename(string $oldEntryUri, string $newEntryUri): bool;

    public function mkdir(string $uri, int $mode, int $flags): bool;

    public function rmdir(string $uri, int $flags): bool;

    /**
     * @return array|bool
     */
    public function url_stat(string $uri, int $flags);

    public function dir_opendir(string $uri, int $flags): bool;

    /**
     * @return string|false Returns false if there is no next file.
     */
    public function dir_readdir(): string|bool;

    public function dir_rewinddir(): bool;

    public function dir_closedir(): bool;
}
