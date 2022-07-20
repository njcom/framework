<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\Php;

use Morpho\Tech\Php\ClassTypeDiscoverer;
use Morpho\Tech\Php\IDiscoverStrategy;
use Morpho\Tech\Php\TokenStrategy;
use Morpho\Testing\TestCase;

use function get_class;

class ClassTypeDiscovererTest extends TestCase {
    private ClassTypeDiscoverer $classTypeDiscoverer;

    public function setUp(): void {
        parent::setUp();
        $this->classTypeDiscoverer = new ClassTypeDiscoverer();
    }

    public function testClassTypesDefinedInDir_UsingDefaultStrategy() {
        $testDirPath = $this->getTestDirPath();
        $ns = __CLASS__;
        $expected = [
            "$ns\\TMyTrait"             => "$testDirPath/Test.php",
            "$ns\\IMyInterface"         => "$testDirPath/Test.php",
            "$ns\\MyClass"              => "$testDirPath/Test.php",
            "$ns\\MyEnum"               => "$testDirPath/Test.php",
            "$ns\\TestMe"               => "$testDirPath/ClassTypeDepsWithStdClasses.php",
            "$ns\\TFourth"              => "$testDirPath/ClassTypeDeps.php",
            "$ns\\IThird"               => "$testDirPath/ClassTypeDeps.php",
            "$ns\\Some"                 => "$testDirPath/ClassTypeDeps.php",
            "$ns\\DefinedInTheSameFile" => "$testDirPath/ClassTypeDeps.php",
            "$ns\\Service"              => "$testDirPath/ClassTypeDeps.php",
            "$ns\\First"                => "$testDirPath/ClassTypeDeps.php",
            "$ns\\Second"               => "$testDirPath/ClassTypeDeps.php",
        ];
        sort($expected);
        $actual = $this->classTypeDiscoverer->classTypesDefinedInDir($testDirPath);
        sort($actual);
        $this->assertSame($expected, $actual);
    }

    public function testDefaultStrategy() {
        $this->assertInstanceOf(TokenStrategy::class, $this->classTypeDiscoverer->discoverStrategy());
    }

    public function testClassTypesDefinedInDir_UsingCustomStrategy() {
        $discoverStrategy = $this->createMock(IDiscoverStrategy::class);
        $discoverStrategy->expects($this->atLeastOnce())
            ->method('classTypesDefinedInFile')
            ->will($this->returnValue([]));
        $this->assertInstanceOf(
            get_class($this->classTypeDiscoverer),
            $this->classTypeDiscoverer->setDiscoverStrategy($discoverStrategy)
        );
        $this->classTypeDiscoverer->classTypesDefinedInDir(__DIR__);
    }

    public function dataClassTestFilePath() {
        return [
            [
                self::class . '\\MyClass',
            ],
            [
                self::class . '\\IMyInterface',
            ],
            [
                self::class . '\\TMyTrait',
            ],
            [
                self::class . '\\MyEnum',
            ],
        ];
    }

    /**
     * @dataProvider dataClassTestFilePath
     */
    public function testClassTypeFilePath(string $class) {
        $filePath = $this->getTestDirPath() . '/Test.php';
        require_once $filePath;
        $this->assertEquals($filePath, ClassTypeDiscoverer::classTypeFilePath($class));
    }

    public function testClassTypeFilePath_ThrowsExceptionOnNonExistingType() {
        $class = self::class . 'NonExisting';
        $this->expectException('ReflectionException', "Class \"$class\" does not exist");
        ClassTypeDiscoverer::classTypeFilePath($class);
    }

    public function testFileDependsFromClassTypes() {
        $classTypes = $this->classTypeDiscoverer->fileDependsFromClassTypes($this->getTestDirPath() . '/ClassTypeDeps.php');
        $this->assertEquals(
            [
                self::class . '\\TraitUsesTrait',
                self::class . '\\FunctionDefinitionHasReturnType',
                self::class . '\\FunctionDefinitionHasParameterWithType',
                self::class . '\\ExtendsInterfaceA',
                self::class . '\\ExtendsInterfaceB',
                self::class . '\\Logger',
                self::class . '\\NullLogger',
                self::class . '\\ClassExtends',
                self::class . '\\ClassImplementsA',
                self::class . '\\ClassImplementsB',
                self::class . '\\ClassUsesTrait',
                self::class . '\\ClassHasPublicProperty',
                self::class . '\\ClassHasProtectedProperty',
                self::class . '\\ClassHasPrivateProperty',
                self::class . '\\InstantiatesNewObject',
                self::class . '\\CallsMethodStatically',
                self::class . '\\ReadsStaticProperty',
                self::class . '\\WritesStaticProperty',
                self::class . '\\CatchesExceptionA',
                self::class . '\\CatchesExceptionB',
                self::class . '\\AppliesInstanceOfOperator',
                self::class . '\\ReadsClassConstant',
                self::class . '\\AnonymousClassExtends',
                self::class . '\\AnonymousClassImplementsA',
                self::class . '\\AnonymousClassImplementsB',
                self::class . '\\AnonymousFunctionDefinitionHasReturnType',
                self::class . '\\AnonymousFunctionDefinitionHasParameterWithType',
                self::class . '\\MethodDefinitionHasReturnType',
                self::class . '\\MethodDefinitionHasParameterWithType',
                self::class . '\\ConstructorDefinitionHasParameterWithType',
            ],
            $classTypes
        );
    }

    public function testFileDependsFromClassTypes_WithoutStdClassesArg() {
        $this->assertEquals(
            [self::class . '\ISome'],
            $this->classTypeDiscoverer->fileDependsFromClassTypes(
                $this->getTestDirPath() . '/ClassTypeDepsWithStdClasses.php'
            )
        );
        $this->assertEquals(
            ['ArrayObject', self::class . '\ISome'],
            $this->classTypeDiscoverer->fileDependsFromClassTypes(
                $this->getTestDirPath() . '/ClassTypeDepsWithStdClasses.php',
                false,
            )
        );
    }
}
