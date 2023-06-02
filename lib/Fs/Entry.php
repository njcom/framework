<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Fs;

use function is_iterable;

abstract class Entry {
    public static function copy(string $srcPath, string $destPath): string {
        return Stat::isDir($srcPath)
            ? Dir::copy($srcPath, $destPath)
            : File::copy($srcPath, $destPath);
    }

    public static function delete(string|iterable $entryPath): void {
        if (is_iterable($entryPath)) {
            foreach ($entryPath as $path) {
                static::delete($path);
            }
            return;
        }
        if (Stat::isDir($entryPath)) {
            Dir::delete($entryPath);
        } else {
            File::delete($entryPath);
        }
    }
}
