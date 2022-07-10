<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\Php;

use Morpho\Tech\Php\IDiscoverStrategy;
use Morpho\Testing\TestCase;

use const Morpho\App\TEST_DATA_DIR_NAME;

abstract class DiscoverStrategyTest extends TestCase {
    protected IDiscoverStrategy $strategy;

    public function setUp(): void {
        parent::setUp();
        $this->strategy = $this->mkDiscoverStrategy();
    }

    protected abstract function mkDiscoverStrategy();

    public function dataClassTypesDefinedInFile() {
        (yield [
            [
                __NAMESPACE__ . '\\StrategyTest1\\FooTrait',
                __NAMESPACE__ . '\\StrategyTest1\\BarClass',
                __NAMESPACE__ . '\\StrategyTest2\\BazInterface',
                __NAMESPACE__ . '\\StrategyTest2\\SomeEnum',
            ],
            'MyFile.php',
        ]);
        (yield [
            [
                'Morpho_Test_Unit_Tech_Php_ReflectionStrategyTest1_Foo',
                'Morpho_Test_Unit_Tech_Php_ReflectionStrategyTest1_One',
                'Morpho_Test_Unit_Tech_Php_ReflectionStrategyTest1\\Bar',
                'Morpho_Test_Unit_Tech_Php_ReflectionStrategyTest1\\Two',
                __NAMESPACE__ . '\\StrategyTest1\\Three',
                __NAMESPACE__ . '\\StrategyTest1\\Baz',
            ],
            'mixed-nss.php',
        ]);
    }

    /**
     * @dataProvider dataClassTypesDefinedInFile
     */
    public function testClassTypesDefinedInFile(array $expected, string $relFilePath) {
        $actual = $this->strategy->classTypesDefinedInFile(__DIR__ . '/' . TEST_DATA_DIR_NAME . '/DiscoverStrategyTest/' . $relFilePath);
        $this->assertEquals($expected, $actual);
    }
}