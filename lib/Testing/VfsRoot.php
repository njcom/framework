<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Testing;

use Closure;
use LogicException;

use function array_shift;
use function count;
use function explode;
use function trim;

class VfsRoot extends VfsDir {
    public function createAllDirs(string $uri, VfsEntryStat $stat): VfsDir {
        return $this->forEachParentDir(
            $uri,
            function (VfsDir $dir, string $name, array $parts, string $curUri) use ($stat) {
                $dir = $dir->createDir($curUri, $stat);
                return [$dir, false];
            }
        );
    }

    private function forEachParentDir(string $uri, Closure $current) {
        $uriNoPrefix = Vfs::stripUriPrefix($uri);
        if ($uriNoPrefix === '/') {
            return $this;
        }
        $entry = $this;
        $parts = explode('/', trim($uriNoPrefix, '/'));
        $curUri = Vfs::prefixUri('');
        $i = 0;
        while (count($parts)) {
            $name = array_shift($parts);
            $curUri .= '/' . $name;
            [$entry, $stop] = $current($entry, $name, $parts, $curUri);
            if ($stop) {
                return $entry;
            }
            $i++;
        }
        if ($i === 0) {
            throw new LogicException();
        }
        return $entry;
    }

    public function dirByUri(string $uri): VfsDir {
        return $this->forEachParentDir(
            $uri,
            function (VfsDir $dir, string $name) {
                $childDir = $dir->dir($name);
                return [$childDir, false];
            }
        );
    }

    /**
     * @return VfsDir|false
     */
    public function dirByUriOrNone(string $uri) {
        return $this->forEachParentDir(
            $uri,
            function (VfsDir $dir, string $name, array $parts) {
                $childDir = $dir->dirOrNone($name);
                if (!$childDir) {
                    return [false, true];
                }
                return [$childDir, false];
            }
        );
    }

    /**
     * @param VfsFile|false
     */
    public function fileByUriOrNone(string $uri) {
        return $this->forEachParentDir(
            $uri,
            function (VfsDir $dir, string $name, array $parts) {
                if (!count($parts)) { // last item?
                    $file = $dir->fileOrNone($name);
                    return [$file, true];
                }
                $childDir = $dir->dirOrNone($name);
                if (!$childDir) {
                    return [false, true];
                }
                return [$childDir, false];
            }
        );
    }

    public function entryByUri(string $uri): IVfsEntry {
        return $this->forEachParentDir(
            $uri,
            function (VfsDir $dir, string $name, array $parts) {
                $childEntry = $dir->entry($name);
                if ($childEntry instanceof VfsFile) {
                    if (count($parts)) {
                        throw new LogicException();
                    }
                    return [$childEntry, true];
                }
                return [$childEntry, false];
            }
        );
    }

    /**
     * @return IVfsEntry|false
     */
    public function entryByUriOrNone(string $uri) {
        return $this->forEachParentDir(
            $uri,
            function (VfsDir $dir, string $name, array $parts) {
                $childEntry = $dir->entryOrNone($name);
                if (!$childEntry) {
                    return [false, true];
                }
                if ($childEntry instanceof VfsFile) {
                    if (count($parts)) { // file in the middle of path
                        throw new LogicException();
                    }
                    return [$childEntry, true];
                } else {
                    return [$childEntry, false];
                }
            }
        );
    }
}
