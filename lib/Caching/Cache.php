<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Caching;

use DateInterval;
use DateTime;
use InvalidArgumentException;
use Traversable;

use function array_fill_keys;
use function array_merge;
use function is_int;

/**
 * This class based on \Doctrine\Common\Cache\CacheProvider from Doctrine project (MIT license).
 * For more information, see <http://www.doctrine-project.org>.
 * Copyright (c) 2006-2015 Doctrine Project
 */

/**
 * This class based on https://github.com/Roave/DoctrineSimpleCache/blob/master/src/SimpleCacheAdapter.php
 * The MIT License (MIT)
 * Copyright (c) 2017 Roave, LLC.
 */
abstract class Cache implements ICache {
    protected const STATS_HITS = 'hits';
    protected const STATS_MISSES = 'misses';
    protected const STATS_UPTIME = 'uptime';
    protected const STATS_MEMORY_USAGE = 'memory_usage';
    protected const STATS_MEMORY_AVAILABLE = 'memory_available';

    public function get(string $key, mixed $default = null): mixed {
        [$found, $value] = $this->fetch($key);
        return $found ? $value : $default;
    }

    /**
     * @return array a tuple, where
     *     the first element must be false in case of cache miss, and true otherwise
     *     the second element must be the actual value in case of success or null in case of cache miss.
     */
    abstract protected function fetch(string $key): array;

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool {
        if ($ttl === null) {
            $ttl = 0;
        } else {
            if ($ttl instanceof DateInterval) {
                $ttl = $this->dateIntervalToInt($ttl);
            }
            if (!is_int($ttl)) {
                throw new InvalidArgumentException('Invalid ttl');
            }
            if ($ttl <= 0) {
                return $this->delete($key);
            }
        }
        return $this->save($key, $value, $ttl);
    }

    private function dateIntervalToInt(DateInterval $ttl): int {
        // todo: check $ttl limitation for 2038 year.
        return (new DateTime())
            ->setTimestamp(0)
            ->add($ttl)
            ->getTimestamp();
    }

    /**
     * Puts data into the cache.
     *
     * @param int $lifeTime If 0 then infinite lifetime.
     * @return bool true if the entry was successfully stored in the cache, false otherwise.
     */
    abstract protected function save(string $key, $data, $lifeTime): bool;

    /**
     * @param array|Traversable $keys
     * @param mixed $default
     * @return array
     */
    public function multi(iterable $keys, mixed $default = null): array {
        return array_merge(array_fill_keys($keys, $default), $this->fetchMultiple($keys));
    }

    protected function fetchMultiple(array $keys) {
        $res = [];
        foreach ($keys as $key) {
            [$found, $value] = $this->fetch($key);
            if ($found) {
                $res[$key] = $value;
            }
        }
        return $res;
    }

    public function setMulti(iterable $values, null|int|DateInterval $ttl = null): bool {
        if ($ttl === null) {
            $ttl = 0;
        } else {
            if ($ttl instanceof DateInterval) {
                $ttl = $this->dateIntervalToInt($ttl);
            }
            if (!is_int($ttl)) {
                throw new InvalidArgumentException('Invalid ttl');
            }
            if ($ttl <= 0) {
                $keys = [];
                foreach ($values as $k => $_) {
                    $keys[] = $k;
                }
                return $this->deleteMulti($keys);
            }
        }
        return $this->saveMulti($values, $ttl);
    }

    public function deleteMulti(iterable $keys): bool {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Default implementation of doSaveMultiple. Each driver that supports multi-put should override it.
     *
     * @param array $keysAndValues Array of keys and values to save in cache
     * @param int $lifetime The lifetime. If != 0, sets a specific lifetime for these
     *                              cache entries (0 => infinite lifeTime).
     */
    protected function saveMulti(iterable $keysAndValues, int $lifetime): bool {
        $success = true;
        foreach ($keysAndValues as $key => $value) {
            if (!$this->save($key, $value, $lifetime)) {
                $success = false;
            }
        }
        return $success;
    }
}
