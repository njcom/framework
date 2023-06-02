<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use Morpho\Base\Converter;
use Morpho\Testing\TestCase;

use function pow;

class ConverterTest extends TestCase {
    public function testToBytes() {
        $this->assertEquals(10 * pow(2, 20), Converter::toBytes('10M'));
    }
}
