<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use ArrayObject;
use Morpho\Base\Conf;
use Morpho\Base\InvalidConfException;
use Morpho\Testing\TestCase;

class ConfTest extends TestCase {
    public function testInterface() {
        $this->assertInstanceOf(ArrayObject::class, new Conf());
    }

    public function testOnlyDefault() {
        $conf = new class extends Conf {
            protected ?array $default = [
                'foo' => 'bar',
            ];
        };
        $this->assertSame(['foo' => 'bar'], $conf->getArrayCopy());
    }

    public function testDefaultWithValues() {
        $conf = new class (['abc' => 123, 'foo' => 'pear']) extends Conf {
            protected ?array $default = [
                'foo' => 'bar',
            ];
        };
        $this->assertSame(['abc' => 123, 'foo' => 'pear'], $conf->getArrayCopy());
    }

    public function testOnlyValues() {
        $data = ['foo' => 'bar'];
        $conf = new Conf($data);
        $this->assertSame($data, $conf->getArrayCopy());
    }

    public function testNoDefaultAndValues() {
        $this->assertSame([], (new Conf())->getArrayCopy());
    }

    public static function dataMerge() {
        yield [
            false,
            ['foo' => ['abc']],
        ];
        yield [
            true,
            ['foo' => ['bar', 'abc']],
        ];
    }

    /**
     * @dataProvider dataMerge
     */
    public function testMerge(bool $recursive, $expected) {
        $conf = new Conf(['foo' => ['bar']]);
        $this->assertSame($expected, $conf->merge(['foo' => ['abc']], $recursive)->getArrayCopy());
    }

    public static function dataCheck_Array() {
        return [
            [
                [],
                [],
                [],
            ],
            [
                [],
                null,
                [],
            ],
            [
                ['foo' => 'my-default'],
                [],
                ['foo' => 'my-default'],
            ],
            [
                ['foo' => 'my-option'],
                ['foo' => 'my-option'],
                ['foo' => 'my-default'],
            ],
            [
                ['foo' => 'my-option'],
                ['foo' => 'my-option'],
                ['foo' => 'my-default'],
            ],
            [
                ['foo' => 'bar'],
                null,
                ['foo' => 'bar'],
            ],
        ];
    }

    /**
     * @dataProvider dataCheck_Array
     */
    public function testCheck_Array($expected, $conf, $defaultConf) {
        $this->assertEquals(
            $expected,
            Conf::check(
                $defaultConf,
                $conf
            )
        );
    }

    public function testCheck_Array_ThrowsExceptionWhenParamsWithDefaultKeysAreMissing() {
        $this->expectException(InvalidConfException::class, "Invalid conf keys: foo");
        Conf::check(['one' => 1], ['foo' => 'bar']);
    }

    public function testCheck_Array_InvalidNumericKeys() {
        $this->expectException(InvalidConfException::class, "Invalid conf keys: 2, 5");
        Conf::check(['foo' => 'baz'], [2 => 'two', 'foo' => 'bar', 5 => 'five']);
    }
}
