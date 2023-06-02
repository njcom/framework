<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Caching;

use Morpho\Caching\ICache;
use Morpho\Caching\TextFileCache;

class TextFileCacheTest extends CacheTest {
    protected function mkCache(): ICache {
        return new TextFileCache($this->createTmpDir());
    }
}
