<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Fs;

use Morpho\Base\Path as BasePath;
use Morpho\Base\NotImplementedException;
use Morpho\Base\SecurityException;

use UnexpectedValueException;

use function array_pop;
use function count;
use function dirname;
use function explode;
use function file_exists;
use function implode;
use function is_file;
use function ltrim;
use function pathinfo;
use function strlen;
use function strpos;
use function substr;

class Path extends BasePath {
    public static function isAbs(string $path): bool {
        return $path !== '' && $path[0] === '/' || self::isAbsWinPath($path);
    }

    public static function isAbsWinPath(string $path): bool {
        return (strlen($path) >= 3 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '/' || $path[2] === '\\'));
    }

    public static function assertSafe(string $path): string {
        if (str_contains($path, "\x00") || str_contains($path, '..')) {
            throw new SecurityException("Invalid file path was detected.");
        }
        return $path;
    }

    public static function normalize(string $path, bool $removeDotSegments = true): string {
        if (self::isAbsWinPath($path)) {
            return str_replace('\\', '/', substr($path, 0, 3)) . parent::normalize(substr($path, 3));
        }
        return parent::normalize($path, $removeDotSegments);
    }

    public static function ext(string $path): string {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    public static function fileName(string $path): string {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    public static function dirPath(string $path): string {
        // Handle paths like vfs:///foo/bar
        $pos = strpos($path, ':');
        if (false !== $pos && preg_match('~^([a-z]+://)(.*)$~si', $path, $match)) {
            return $match[1] . dirname($match[2]);
        }
        return dirname($path);
    }

    /**
     * @param string      $path
     * @param string|null $ext If null will guess it.
     * @return string
     */
    public static function dropExt(string $path, string $ext = null): string {
        if (null !== $ext) {
            $ext = self::normalizeExt($ext);
            if (str_ends_with($path, $ext)) {
                return substr($path, 0, -strlen($ext));
            }
            return $path;
        }
        $pos = strrpos($path, '.');
        if (false !== $pos) {
            return substr($path, 0, $pos);
        }
        return $path;
    }

    public static function changeExt(string $path, string $newExt, string $oldExt = null): string {
        if ($path === '' || $newExt === '') {
            throw new UnexpectedValueException("Path or extension can't be empty");
        }
        $newExt = self::normalizeExt($newExt);
        if (!str_ends_with($path, $newExt)) {
            return self::dropExt($path, $oldExt) . $newExt;
        }
        return $path;
    }

    public static function normalizeExt(string $ext): string {
        if (strlen($ext)) {
            return '.' . ltrim($ext, '.');
        }
        return '';
    }

    public static function nameWithoutExt(string $path): string {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Returns unique path for a file system entry.
     * NB: This is not safe if multiple threads (processes) can work with the same $path.
     */
    public static function unique(
        string $path,
        ?bool $handleExtsForFiles = true,
        int $numberOfAttempts = 10000
    ): string {
        Dir::mustExist(dirname($path));
        $uniquePath = $path;
        $isFile = is_file($path);
        for ($i = 0; file_exists($uniquePath) && $i < $numberOfAttempts; $i++) {
            if ($isFile && $handleExtsForFiles) {
                $pathInfo = pathinfo($path);
                $uniquePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-' . $i . (isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '');
            } else {
                $uniquePath = $path . '-' . $i;
            }
        }
        if ($i == $numberOfAttempts && file_exists($uniquePath)) {
            throw new Exception("Unable to generate an unique path for the '$path' (tried $i times)");
        }
        return $uniquePath;
    }

    public static function parentPaths(string $path): array {
        throw new NotImplementedException();
    }
}
