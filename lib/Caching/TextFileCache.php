<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Caching;

use RuntimeException;

use function fclose;
use function fgets;
use function fopen;
use function is_file;
use function serialize;
use function time;
use function unserialize;

/**
 * This class based on \Doctrine\Common\Cache\FilesystemCache from Doctrine project (MIT license).
 * For more information, see <http://www.doctrine-project.org>.
 * Copyright (c) 2006-2015 Doctrine Project
 */
class TextFileCache extends FileCache {
    protected const EXTENSION = '.cache';

    public function __construct(string $dirPath, string $extension = null, int $umask = 0002) {
        parent::__construct($dirPath, $extension ?: self::EXTENSION, $umask);
    }

    public function has($key): bool {
        $lifetime = -1;
        $filename = $this->cacheFilePath($key);
        if (!is_file($filename)) {
            return false;
        }
        $handle = fopen($filename, 'r');
        if (!$handle) {
            throw new RuntimeException('Unable to open file for reading');
        }
        try {
            if (false !== ($line = fgets($handle))) {
                $lifetime = (int) $line;
            }
        } finally {
            fclose($handle);
        }
        return $lifetime === 0 || $lifetime > time();
    }

    protected function fetch(string $key): array {
        $data = '';
        $lifetime = -1;
        $filename = $this->cacheFilePath($key);
        if (!is_file($filename)) {
            return [false, null];
        }
        $handle = fopen($filename, 'r');
        if (!$handle) {
            throw new RuntimeException('Unable to open file for reading');
        }
        try {
            if (false !== ($line = fgets($handle))) {
                $lifetime = (int) $line;
            }
            if ($lifetime !== 0 && $lifetime < time()) {
                fclose($handle);
                return [false, null];
            }
            while (false !== ($line = fgets($handle))) {
                $data .= $line;
            }
        } finally {
            fclose($handle);
        }
        return [true, unserialize($data)];
    }

    protected function save(string $key, $data, $lifeTime = 0): bool {
        if ($lifeTime > 0) {
            $lifeTime = time() + $lifeTime;
        }
        $data = serialize($data);
        $cacheFilePath = $this->cacheFilePath($key);
        return $this->writeFile($cacheFilePath, $lifeTime . PHP_EOL . $data);
    }
}
