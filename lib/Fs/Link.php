<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Fs;

use function basename;
use function dirname;
use function file_exists;
use function is_file;
use function is_link;
use function symlink;

class Link extends Entry {
    public static function create($targetPath, $linkPath) {
        // @TODO: Handle the case when the \dirname($linkPath) is symlink to some directory
        // it can be link to file, but in this case it is an error.
        Dir::create(dirname($linkPath));
        if (is_file($targetPath) && Stat::isDir($linkPath)) {
            $linkPath = $linkPath . '/' . basename($targetPath);
        }
        if (!@symlink($targetPath, $linkPath)) {
            throw new Exception("Unable to create symlink '$linkPath' for target '$targetPath'");
        }
    }

    public static function isBroken(string $linkPath): bool {
        if (!is_link($linkPath)) {
            throw new Exception('The passed path is not a symlink');
        }
        return !file_exists($linkPath);
        /*

        $targetPath = readlink($linkPath);
        if (false === $targetPath) {
            return false;
        }
        if (Path::isAbs($targetPath)) {
            return !Stat::isEntry($targetPath);
        }
        $curDirPath = getcwd();
        chdir(\dirname($linkPath));
        try {
            return !Stat::isEntry($targetPath);
        } finally {
            chdir($curDirPath);
        }
        */
    }
}
