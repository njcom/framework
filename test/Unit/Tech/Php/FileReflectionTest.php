<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\Php;

use Closure;
use Generator;
use Morpho\Tech\Php\ClassTypeReflection;
use Morpho\Tech\Php\FileReflection;
use Morpho\Tech\Php\NamespaceReflection;
use Morpho\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionFunction;

use function count;
use function iterator_to_array;

class FileReflectionTest extends TestCase {
    public function testFilePath() {
        $filePath = $this->getTestDirPath() . '/empty-file.php';
        $rFile = new FileReflection($filePath);
        $this->assertEquals($filePath, $rFile->filePath());
        $this->assertSame([], iterator_to_array($rFile->namespaces(), false));
        $this->assertSame([], iterator_to_array($rFile->classes(), false));
        $this->assertSame([], iterator_to_array($rFile->traits(), false));
        $this->assertSame([], iterator_to_array($rFile->enums(), false));
    }

    public function testClasses() {
        $this->checkClasses(
            'classes',
            2,
            function ($i, $rClass) {
                switch ($i) {
                    case 0:
                        $this->assertSame(__CLASS__ . '\\ServiceManager', $rClass->getName());
                        break;
                    case 1:
                        $this->assertSame(__CLASS__ . '\\ServiceNotFoundException', $rClass->getName());
                        break;
                }
            }
        );
    }

    private function checkClasses(string $method, int $expectedCount, Closure $check) {
        $filePath = $this->getTestDirPath() . '/classes.php';
        $rFile = new FileReflection($filePath);
        $i = 0;
        foreach ($rFile->{$method}() as $rClass) {
            /** @var $rClass ClassTypeReflection */
            $this->assertInstanceOf(ReflectionClass::class, $rClass);
            $check($i, $rClass);
            $this->assertSame(__CLASS__, $rClass->getNamespaceName());
            $i++;
        }
        $this->assertSame($expectedCount, $i);
    }

    public function testTraits() {
        $this->checkClasses(
            'traits',
            1,
            function ($i, $rClass) {
                switch ($i) {
                    case 0:
                        $this->assertSame(__CLASS__ . '\\THasServiceManager', $rClass->getName());
                        break;
                }
            }
        );
    }

    public function testInterface() {
        $this->checkClasses(
            'interfaces',
            2,
            function ($i, $rClass) {
                switch ($i) {
                    case 0:
                        $this->assertSame(__CLASS__ . '\\IHasServiceManager', $rClass->getName());
                        break;
                    case 1:
                        $this->assertSame(__CLASS__ . '\\IServiceManager', $rClass->getName());
                        break;
                }
            }
        );
    }

    public function testNamespaces_EmptyFile() {
        $filePath = $this->getTestDirPath() . '/empty-file.php';
        $rFile = new FileReflection($filePath);
        $this->assertGenYields([], $rFile->namespaces());
    }

    private function assertGenYields($expected, Generator $gen) {
        $this->assertEquals($expected, iterator_to_array($gen, false));
    }

    public function testNamespaces_GlobalNamespace() {
        $filePath = $this->getTestDirPath() . '/global-ns.php';
        $rFile = new FileReflection($filePath);
        $i = 0;
        foreach ($rFile->namespaces() as $rNamespace) {
            $this->assertInstanceOf(NamespaceReflection::class, $rNamespace);
            $this->assertNull($rNamespace->name());
            $this->assertTrue($rNamespace->isGlobal());
            $this->assertEquals($filePath, $rNamespace->filePath());
            $this->checkClassTypes(
                [
                    'Bar4f978258c3d87c0711d6f17c6b4ecfcd',
                    'TMoon4f978258c3d87c0711d6f17c6b4ecfcd',
                    'Morpho_Test_Unit_Tech_Php_FileReflectionTest_MyIface',
                    'Morpho_Test_Unit_Tech_Php_FileReflectionTest_MyEnum',
                ],
                $filePath,
                $rNamespace
            );
            $this->checkFunctions(['foo4f978258c3d87c0711d6f17c6b4ecfcd'], $filePath, $rNamespace);
            $i++;
        }
        $this->assertEquals(1, $i);
    }

    private function checkClassTypes(array $expectedClasses, string $filePath, NamespaceReflection $rNamespace) {
        $j = 0;
        foreach ($rNamespace->classTypes() as $rClass) {
            $this->checkReflectionClass($expectedClasses[$j], $filePath, $rClass);
            $j++;
        }
        $this->assertEquals(count($expectedClasses), $j);
    }

    private function checkReflectionClass(
        string $expectedClass,
        string $expectedFilePath,
        ClassTypeReflection $rClass
    ) {
        $this->assertEquals($expectedClass, $rClass->getName());
        $this->assertEquals($expectedFilePath, $rClass->getFileName());
    }

    private function checkFunctions(array $expectedFns, string $filePath, NamespaceReflection $rNamespace) {
        $j = 0;
        foreach ($rNamespace->functions() as $rFunction) {
            $this->checkReflectionFunction($expectedFns[$j], $filePath, $rFunction);
            $j++;
        }
        $this->assertEquals(count($expectedFns), $j);
    }

    private function checkReflectionFunction(
        string $expectedFnName,
        string $expectedFilePath,
        ReflectionFunction $rFunction
    ) {
        $this->assertEquals($expectedFnName, $rFunction->getName());
        $this->assertEquals($expectedFilePath, $rFunction->getFileName());
    }

    /**
     * @throws \ReflectionException
     */
    public static function dataNamespaces_MultipleNamespaces() {
        $testDirPath = self::getTestDirPath();
        return [
            [
                $testDirPath . '/multiple-bracketed-ns.php',
                [
                    [
                        'classes'   => [__CLASS__ . '_Ns1Bracketed\\First', __CLASS__ . '_Ns1Bracketed\\Second'],
                        'functions' => [__CLASS__ . '_Ns1Bracketed\\foo', __CLASS__ . '_Ns1Bracketed\\bar'],
                    ],
                    [
                        'classes'   => [__CLASS__ . '_Ns2Bracketed\\Third', __CLASS__ . '_Ns2Bracketed\\TFourth'],
                        'functions' => [__CLASS__ . '_Ns2Bracketed\\baz'],
                    ],
                ],
            ],
            [
                $testDirPath . '/multiple-unbracketed-ns.php',
                [
                    [
                        'classes'   => [__CLASS__ . '_Ns1Unbracketed\\First', __CLASS__ . '_Ns1Unbracketed\\Second'],
                        'functions' => [__CLASS__ . '_Ns1Unbracketed\\foo', __CLASS__ . '_Ns1Unbracketed\\bar'],
                    ],
                    [
                        'classes'   => [__CLASS__ . '_Ns2Unbracketed\\Third', __CLASS__ . '_Ns2Unbracketed\\TFourth'],
                        'functions' => [__CLASS__ . '_Ns2Unbracketed\\baz'],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('dataNamespaces_MultipleNamespaces')]
    public function testNamespaces_MultipleNamespaces($filePath, $expected) {
        $rFile = new FileReflection($filePath);
        $i = 0;
        foreach ($rFile->namespaces() as $rNamespace) {
            $this->assertInstanceOf(NamespaceReflection::class, $rNamespace);
            $this->assertFalse($rNamespace->isGlobal());
            $this->assertEquals($filePath, $rNamespace->filePath());
            $this->checkClassTypes($expected[$i]['classes'], $filePath, $rNamespace);
            $this->checkFunctions($expected[$i]['functions'], $filePath, $rNamespace);
            $i++;
        }
        $this->assertEquals(2, $i);
    }
}
