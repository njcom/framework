<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\Php;

use Morpho\Caching\ICache;
use Morpho\Tech\Php\ClassTypeMapAutoloader;
use Morpho\Testing\TestCase;

class ClassTypeMapAutoloaderTest extends TestCase {
    public function testAutoload() {
        $dirPath = $this->getTestDirPath();
        $autoloader = new ClassTypeMapAutoloader($dirPath, '{\\.php$}si');
        $class = __CLASS__ . '\\Foo';
        $this->assertFalse($autoloader->autoload($class . 'Invalid'));
        $this->assertTrue($autoloader->autoload($class));
    }

    public function testClearMap_ClearEmptyMapDoesNotThrowException() {
        $autoloader = new ClassTypeMapAutoloader($this->getTestDirPath());
        $autoloader->clearMap();
        $this->markTestAsNotRisky();
    }

    public function testCaching_Clearing() {
        $cache = $this->createMock(ICache::class);
        $cache->expects($this->once())->method('clear');
        $autoloader = new ClassTypeMapAutoloader($this->getTestDirPath(), null, $cache);
        $autoloader->clearMap();
    }
}