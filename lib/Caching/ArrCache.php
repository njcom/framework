<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Caching;

use function time;

/**
 * This class is based on \Doctrine\Common\Cache\ArrayCache from Doctrine project (MIT license).
 * For more information, see <http://www.doctrine-project.org>.
 * Copyright (c) 2006-2015 Doctrine Project
 */
class ArrCache extends Cache {
    /**
     * @var array[] $data each element being a tuple of [$data, $expiration], where the expiration is int|bool
     */
    private array $data = [];

    private int $hitsCount = 0;

    private int $missesCount = 0;

    private int $upTime;

    public function __construct() {
        $this->upTime = time();
    }

    public function stats(): ?array {
        return [
            Cache::STATS_HITS             => $this->hitsCount,
            Cache::STATS_MISSES           => $this->missesCount,
            Cache::STATS_UPTIME           => $this->upTime,
            Cache::STATS_MEMORY_USAGE     => null,
            Cache::STATS_MEMORY_AVAILABLE => null,
        ];
    }

    public function clear(): bool {
        $this->data = [];
        return true;
    }

    public function has(string $key): bool {
        if (!isset($this->data[$key])) {
            return false;
        }
        $expiration = $this->data[$key][1];
        if ($expiration && $expiration < time()) {
            $this->delete($key);
            return false;
        }
        return true;
    }

    public function delete(string $key): bool {
        unset($this->data[$key]);
        return true;
    }

    protected function fetch(string $key): array {
        if (!isset($this->data[$key])) {
            $this->missesCount += 1;
            return [false, null];
        }
        $this->hitsCount += 1;
        return [true, $this->data[$key][0]];
    }

    protected function save(string $key, $data, $lifeTime = 0): bool {
        $this->data[$key] = [$data, $lifeTime ? time() + $lifeTime : false];
        return true;
    }
}
