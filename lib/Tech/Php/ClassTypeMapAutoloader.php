<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use Morpho\Caching\ICache;

use function Morpho\Caching\cacheKey;

class ClassTypeMapAutoloader extends Autoloader {
    protected $processor;

    protected string|iterable $searchDirPaths;

    protected ?array $map = null;

    protected ?ICache $cache;

    protected string $cacheKey;

    public function __construct(string|iterable $searchDirPaths, string|callable $processor = null, ICache $cache = null) {
        $this->searchDirPaths = $searchDirPaths;
        $this->processor = $processor;
        $this->cache = $cache;
        $this->cacheKey = cacheKey($this, __FUNCTION__);
    }

    public function filePath(string $class): string|false {
        if (null === $this->map) {
            $useCache = null !== $this->cache;
            if ($useCache) {
                if (!$this->cache->has($this->cacheKey)) {
                    $this->map = $this->mkMap();
                    $this->cache->set($this->cacheKey, $this->map);
                } else {
                    $this->map = $this->cache->get($this->cacheKey);
                }
            } else {
                $this->map = $this->mkMap();
            }
        }
        return $this->map[$class] ?? false;
    }

    protected function mkMap(): array {
        $classTypeDiscoverer = new ClassTypeDiscoverer();
        return $classTypeDiscoverer->classTypesDefinedInDir(
            $this->searchDirPaths,
            $this->processor,
            ['followLinks' => true]
        );
    }

    public function clearMap(): void {
        $this->map = null;
        if (null !== $this->cache) {
            $this->cache->clear();
        }
    }
}
