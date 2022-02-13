<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Base;

use RuntimeException;

use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function rtrim;
use function str_replace;
use function strlen;
use function strpos;
use function substr;

abstract class Path {
    public static function rel(string $basePath, string $path): string {
        $path = static::normalize($path);
        $basePath = static::normalize($basePath);

        if ($path === '') {
            return $basePath;
        }
        if ($basePath === '') {
            return $path;
        }
        $pos = strpos($path, $basePath);
        if ($pos !== 0) {
            throw new RuntimeException("The path '$path' does not contain the base path '$basePath'");
        }

        return substr($path, strlen($basePath) + 1);
    }

    public static function normalize(string $path, bool $removeDotSegments = true): string {
        if ($path === '') {
            return $path;
        }
        $path = str_replace('\\', '/', $path);
        if ($path === '/') {
            return $path;
        }
        if ($removeDotSegments) {
            $path = static::removeDotSegments($path);
        }
        return rtrim($path, '/\\');
    }

    /**
     * This method taken from https://github.com/zendframework/zend-uri/blob/master/src/Uri.php and changed to match our requirements.
     *
     * @link      http://github.com/zendframework/zf2 for the canonical source repository
     * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
     * @license   http://framework.zend.com/license/new-bsd New BSD License
     *
     * Remove any extra dot segments (/../, /./) from a path
     *
     * Algorithm is adapted from RFC-3986 section 5.2.4
     * (@link http://tools.ietf.org/html/rfc3986#section-5.2.4)
     *
     * @TODO   optimize
     */
    public static function removeDotSegments(string $path): string {
        $result = '';

        while ($path) {
            if ($path == '..' || $path == '.') {
                break;
            }

            switch (true) {
                case ($path == '/.'):
                    $path = '/';
                    break;
                case ($path == '/..'):
                    $path = '/';
                    $lastSlashPos = lastPos($result, '/', -1);
                    if (false === $lastSlashPos) {
                        break;
                    }
                    $result = mb_substr($result, 0, $lastSlashPos);
                    break;
                case (mb_substr($path, 0, 4) == '/../'):
                    $path = '/' . mb_substr($path, 4);
                    $lastSlashPos = lastPos($result, '/', -1);
                    if (false === $lastSlashPos) {
                        break;
                    }
                    $result = mb_substr($result, 0, $lastSlashPos);
                    break;
                case (mb_substr($path, 0, 3) == '/./'):
                    $path = mb_substr($path, 2);
                    break;
                case (mb_substr($path, 0, 2) == './'):
                    $path = mb_substr($path, 2);
                    break;
                case (mb_substr($path, 0, 3) == '../'):
                    $path = mb_substr($path, 3);
                    break;
                default:
                    $slash = mb_strpos($path, '/', 1);
                    if ($slash === false) {
                        $seg = $path;
                    } else {
                        $seg = mb_substr($path, 0, $slash);
                    }

                    $result .= $seg;
                    $path = mb_substr($path, mb_strlen($seg));
                    break;
            }
        }

        return $result;
    }

    public static function combine(...$paths): string {
        $paths = unpackArgs($paths);
        $result = '';
        $prev = null;
        foreach ($paths as $path) {
            $path = (string) $path;
            if ($path === '') {
                continue;
            }
            if ($path === '/' || $path === '\\') {
                if (null !== $prev) {
                    continue;
                }
                $result = $path;
            } else {
                if (null !== $prev) {
                    $lastChOfPrev = substr($prev, -1, 1);
                    $result .= ($lastChOfPrev === '/' || $lastChOfPrev === '\\' ? '' : '/') . ltrim($path, '/');
                } else {
                    $result .= $path; // first item
                }
            }
            $prev = $path;
        }
        return $result;
    }
}
