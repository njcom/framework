<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App;

use Morpho\App\IModuleIndexer;
use Morpho\App\Module;
use Morpho\App\ModuleIndex;
use Morpho\Testing\TestCase;
use RuntimeException;
use Traversable;

use function in_array;

class ModuleIndexTest extends TestCase {
    public function testRebuild() {
        $moduleIndexer = $this->createMock(IModuleIndexer::class);
        $moduleName = 'foo/bar';
        $moduleIndexer->expects($this->exactly(2))
            ->method('index')
            ->willReturnOnConsecutiveCalls([$moduleName => ['first']], [$moduleName => ['second']]);

        $moduleIndex = $this->mkModuleIndex($moduleIndexer);
        $this->assertSame('first', $moduleIndex->module($moduleName)[0]);

        $moduleIndexer->expects($this->once())
            ->method('clear');

        $this->assertNull($moduleIndex->rebuild());

        $this->assertSame('second', $moduleIndex->module($moduleName)[0]);
    }

    private function mkModuleIndex($moduleIndexer) {
        return new class ($moduleIndexer) extends ModuleIndex {
            protected function mkModule(string $moduleName, $meta): Module {
                return new Module($moduleName, $meta);
            }
        };
    }

    public function testModuleOperations() {
        $moduleIndex = $this->mkModuleIndex($this->mkModuleIndexer());

        $this->assertSame(['galaxy/neptune', 'galaxy/mars'], $moduleIndex->moduleNames());

        $this->assertTrue($moduleIndex->moduleExists('galaxy/neptune'));
        $this->assertFalse($moduleIndex->moduleExists('galaxy/invalid'));
    }

    private function mkModuleIndexer() {
        $moduleIndexer = $this->createConfiguredMock(
            IModuleIndexer::class,
            [
                'index' => [
                    'galaxy/neptune' => [
                        'namespace' => __CLASS__ . '/Neptune',
                    ],
                    'galaxy/mars'    => [
                        'namespace' => __CLASS__ . '/Mars',
                    ],
                ],
            ]
        );
        return $moduleIndexer;
    }

    public function testModule_ThrowsExceptionForNonExistentModule() {
        $moduleIndex = $this->mkModuleIndex($this->mkModuleIndexer());
        $moduleName = 'galaxy/foo';
        $this->expectException(RuntimeException::class, "The module '$moduleName' was not found in index");
        $moduleIndex->module($moduleName);
    }

    public function testIter() {
        $moduleIndex = $this->mkModuleIndex($this->mkModuleIndexer());
        $this->assertInstanceOf(Traversable::class, $moduleIndex);
        $i = 0;
        foreach ($moduleIndex as $moduleName) {
            $this->assertTrue(in_array($moduleName, ['galaxy/neptune', 'galaxy/mars'], true));
            $i++;
        }
        $this->assertSame(2, $i);
    }
}
