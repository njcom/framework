<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\ActionMetaProvider;
use Morpho\Testing\TestCase;

use PHPUnit\Framework\Attributes\DataProvider;

use function iterator_to_array;

class ActionMetaProviderTest extends TestCase {
    private ActionMetaProvider $actionMetaProvider;

    protected function setUp(): void {
        parent::setUp();
        $this->actionMetaProvider = new ActionMetaProvider();
    }

    public function testInterface() {
        $this->assertIsCallable($this->actionMetaProvider);
    }

    /**
     * @throws \ReflectionException
     */
    public static function dataInvoke(): iterable {
        $testDirPath = self::getTestDirPath();
        $testNs = __CLASS__;
        yield [
            ['self/box', $testDirPath . '/inheritance/SecondParentController.php'],
            [
                // SecondParentController
                [
                    'module'   => 'self/box',
                    'class'    => $testNs . '\\SecondParentController',
                    'filePath' => $testDirPath . '/inheritance/SecondParentController.php',
                    'method'   => 'secondParent',
                ],
            ],
        ];
        yield [
            ['store/product', $testDirPath . '/inheritance/FirstParentController.php'],
            [
                // FirstParentController extends SecondParentController
                [
                    'module'   => 'store/product',
                    'class'    => $testNs . '\\FirstParentController',
                    'filePath' => $testDirPath . '/inheritance/FirstParentController.php',
                    'method'   => 'firstParent',
                ],
                [
                    'module'   => 'store/product',
                    'class'    => $testNs . '\\FirstParentController',
                    'filePath' => $testDirPath . '/inheritance/FirstParentController.php',
                    'method'   => 'secondParent',
                ],
            ],
        ];
        yield [
            ['store/product', $testDirPath . '/inheritance/ChildController.php'],
            [
                // ChildController extends FirstParentController
                [
                    'module'   => 'store/product',
                    'class'    => $testNs . '\\ChildController',
                    'filePath' => $testDirPath . '/inheritance/ChildController.php',
                    'method'   => 'child',
                ],
                [
                    'module'   => 'store/product',
                    'class'    => $testNs . '\\ChildController',
                    'filePath' => $testDirPath . '/inheritance/ChildController.php',
                    'method'   => 'firstParent',
                ],
                [
                    'module'   => 'store/product',
                    'class'    => $testNs . '\\ChildController',
                    'filePath' => $testDirPath . '/inheritance/ChildController.php',
                    'method'   => 'secondParent',
                ],
            ],
        ];
        yield [
            ['foo/bar', $testDirPath . '/My2Controller.php'],
            [
                // MyFirst2Controller extends Controller
                [
                    'module'   => 'foo/bar',
                    'class'    => $testNs . '\\MyFirst2Controller',
                    'filePath' => $testDirPath . '/My2Controller.php',
                    'method'   => 'foo2',
                ],
                // MySecond2Controller extends Controller
                [
                    'module'   => 'foo/bar',
                    'class'    => $testNs . '\\MySecond2Controller',
                    'filePath' => $testDirPath . '/My2Controller.php',
                    'method'   => 'doSomething2',
                ],
                // Third2Controller extends Controller
                [
                    'module'     => 'foo/bar',
                    'class'      => $testNs . '\\MySecond2Controller',
                    'filePath'   => $testDirPath . '/My2Controller.php',
                    'docComment' => '/**
     * @foo Bar
     */',
                    'method'     => 'process2',
                ],
            ],
        ];
        yield [
            ['random/planet', $testDirPath . '/My1Controller.php'],
            [
                // My1FirstController extends Controller
                [
                    'module'   => 'random/planet',
                    'class'    => $testNs . '\\My1FirstController',
                    'filePath' => $testDirPath . '/My1Controller.php',
                    'method'   => 'foo1',
                ],
                // MySecond1Controller extends Controller
                [
                    'module'   => 'random/planet',
                    'class'    => $testNs . '\\MySecond1Controller',
                    'filePath' => $testDirPath . '/My1Controller.php',
                    'method'   => 'doSomething1',
                ],
                [
                    'module'     => 'random/planet',
                    'class'      => $testNs . '\\MySecond1Controller',
                    'filePath'   => $testDirPath . '/My1Controller.php',
                    'method'     => 'process1',
                    'docComment' => '/**
     * @foo Bar
     */',
                ],
            ],
        ];
        yield [
            ['sunny/day', $testDirPath . '/My3Controller.php'],
            [
                // MyFirst3Controller extends Controller
                [
                    'module'   => 'sunny/day',
                    'class'    => $testNs . '\\MyFirst3Controller',
                    'filePath' => $testDirPath . '/My3Controller.php',
                    'method'   => 'foo3',
                ],
                // MySecond3Controller extends Controller
                [
                    'module'   => 'sunny/day',
                    'class'    => $testNs . '\\MySecond3Controller',
                    'filePath' => $testDirPath . '/My3Controller.php',
                    'method'   => 'doSomething3',
                ],
                // MyThird3Controller
                [
                    'module'     => 'sunny/day',
                    'class'      => $testNs . '\\MySecond3Controller',
                    'filePath'   => $testDirPath . '/My3Controller.php',
                    'method'     => 'process3',
                    'docComment' => '/**
     * @foo Bar
     */',
                ],
            ],
        ];
        yield [
            ['one/more', $testDirPath . '/BaseController.php'],
            [
            ],
        ];
        yield [
            ['one/more', $testDirPath . '/NotClassController.php'],
            [
            ],
        ];
        yield [
            ['one/more', $testDirPath . '/NotClass1Controller.php'],
            [
            ],
        ];
    }

    #[DataProvider('dataInvoke')]
    public function testInvoke($moduleMeta, $expected) {
        $module = $this->mkModule($moduleMeta[0], $moduleMeta[1]);
        $actual = iterator_to_array($this->actionMetaProvider->__invoke([$module]), false);
        $this->assertEquals($expected, $actual);
    }

    private function mkModule(string $name, $controllerFilePaths) {
        return new class ($name, $controllerFilePaths) {
            private string $name;
            private array $controllerFilePaths;

            public function __construct(string $name, string $controllerFilePaths) {
                $this->name = $name;
                $this->controllerFilePaths = (array) $controllerFilePaths;
            }

            public function name() {
                return $this->name;
            }

            public function controllerFilePaths(): iterable {
                return $this->controllerFilePaths;
            }
        };
    }

    public function testInvoke_NoRoutesAnnotation() {
        $module = $this->mkModule('test/annotations', $this->getTestDirPath() . '/NoRoutesController.php');
        $this->assertSame([], iterator_to_array($this->actionMetaProvider->__invoke([$module])));
    }

    public function testInvoke_SkipsMagicAndUnderscoredMethods() {
        $module = $this->mkModule('foo/bar', $this->getTestDirPath() . '/HavingMagicMethodsController.php');

        $this->assertSame(
            [
                [
                    'module'   => 'foo/bar',
                    'class'    => __CLASS__ . '\\HavingMagicMethodsController',
                    'filePath' => $this->getTestDirPath() . '/HavingMagicMethodsController.php',
                    'method'   => 'playMe',
                ],
            ],
            iterator_to_array($this->actionMetaProvider->__invoke([$module]))
        );
    }
}
