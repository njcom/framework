<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\Php;

use Morpho\Tech\Php\DiffStrategy;

use PHPUnit\Framework\Attributes\DataProvider;

use function sort;

use const Morpho\App\TEST_DATA_DIR_NAME;

class DiffStrategyTest extends DiscoverStrategyTest {
    #[DataProvider('dataClassTypesDefinedInFile')]
    public function testClassTypesDefinedInFile(array $expected, string $relFilePath) {
        $actual = $this->strategy->classTypesDefinedInFile(__DIR__ . '/' . TEST_DATA_DIR_NAME . '/DiscoverStrategyTest/' . $relFilePath);
        // @todo: fix sorting
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }

    protected function mkDiscoverStrategy() {
        return new DiffStrategy();
    }
}
