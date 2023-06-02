<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use ArrayAccess;
use Morpho\Caching\ICache;

use function Morpho\Caching\cacheKey;
use function uasort;

class ModuleIndexer implements IModuleIndexer {
    private ICache $cache;
    private string $cacheKey;
    private iterable $moduleIt;

    public function __construct(iterable $moduleIt, ICache $cache) {
        $this->moduleIt = $moduleIt;
        $this->cache = $cache;
        $this->cacheKey = cacheKey($this, __FUNCTION__);
    }

    /**
     * Indexes all modules and returns the index. Can cache the result.
     */
    public function index(): array|ArrayAccess {
        $cacheKey = $this->cacheKey;
        $index = $this->cache->get($cacheKey);
        if (null !== $index) {
            return $index;
        }
        $index = [];
        foreach ($this->moduleIt as $module) {
            $index[$module['name']] = $module;
        }
        uasort(
            $index,
            function ($a, $b) {
                return $a['weight'] - $b['weight'];
            }
        );
        $this->cache->set($cacheKey, $index);
        return $index;
    }

    /**
     * Clears the internal state and cache so that the next call of the index() will build a new index.
     */
    public function clear(): void {
        $this->cache->delete($this->cacheKey);
    }
}
