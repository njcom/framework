<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use Morpho\Base\Must;
use Morpho\Base\MustException;
use Morpho\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MustTest extends TestCase {
    public function testBeTruthy_Valid() {
        $this->assertSame(STDERR, Must::beTruthy(STDERR));
    }

    public function testBeTruthy_Invalid() {
        $this->expectException(MustException::class);
        Must::beTruthy(false);
    }

    public function testBeNull_Valid() {
        $this->assertNull(Must::beNull(null));
    }

    public function testBeNull_Invalid() {
        $this->expectException(MustException::class);
        Must::beNull(false);
    }

    public function testBeNotNull_Valid() {
        $this->assertSame(123, Must::beNotNull(123));
    }

    public function testBeNotNull_Invalid() {
        $this->expectException(MustException::class);
        Must::beNotNull(null);
    }

    public function testBeEmpty_Invalid() {
        $this->expectExceptionObject(new MustException('The value must be empty'));
        Must::beEmpty('abc');
    }

    public static function dataBeEmpty_Valid(): iterable {
        yield [''];
        yield [null];
        yield [0];
        yield [false];
        yield [[]];
        yield [0.0];
    }

    #[DataProvider('dataBeEmpty_Valid')]
    public function testBeEmpty_Valid($v) {
        $this->assertSame($v, Must::beEmpty($v));
    }

    public static function dataBeNotEmpty_Invalid(): iterable {
        return [
            [
                '',
                false,
                null,
                0,
                0.0,
                [],
            ],
        ];
    }

    #[DataProvider('dataBeNotEmpty_Invalid')]
    public function testBeNotEmpty_Invalid($v) {
        $this->expectExceptionObject(new MustException("The value must be non empty"));
        Must::beNotEmpty($v);
    }

    public function testBeNotEmpty_Valid() {
        $v = ['foo', 123, 3.14, ["Hello"]];
        $this->assertSame($v, Must::beNotEmpty($v));
        $v = 'abc';
        $this->assertSame($v, Must::beNotEmpty($v));
    }

    public function testContain_String_Valid() {
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertNull(Must::contain('foo/bar', '/'));
    }

    public function testContain_String_Invalid() {
        $this->expectExceptionObject(new MustException('A haystack does not contain a needle'));
        Must::contain('foo-bar', '/');
    }

    public static function dataContain_Array_Valid(): iterable {
        return [
            [
                [null, 0, '', false],
                '',
            ],
            [
                ['bar', 'baz', 'foo'],
                'foo',
            ],
            [
                ['foo', ['bar']],
                ['bar'],
            ],
        ];
    }

    #[DataProvider('dataContain_Array_Valid')]
    public function testContain_Array_Valid($haystack, $needle) {
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertNull(Must::contain($haystack, $needle));
    }

    public static function dataContain_Array_Invalid(): iterable {
        return [
            [
                [],
                null,
            ],
            [
                [null, 0, false],
                '',
            ],
            [
                ['bar', 'baz'],
                'foo',
            ],
        ];
    }

    #[DataProvider('dataContain_Array_Invalid')]
    public function testContain_Array_Invalid($haystack, $needle) {
        $this->expectExceptionObject(new MustException('A haystack does not contain a needle'));
        Must::contain($haystack, $needle);
    }

    public static function dataHaveAtLeastKeys_Invalid(): iterable {
        return [
            [
                ['foo' => 1, 'baz' => 2],
                ['foo', 'bar'],
            ],
            [
                ['foo' => 1],
                ['foo', 'bar'],
            ],
            [
                [],
                ['foo', 'bar'],
            ],
        ];
    }

    #[DataProvider('dataHaveAtLeastKeys_Invalid')]
    public function testHaveAtLeastKeys_Invalid($actual, $requiredKeys) {
        $this->expectExceptionObject(new MustException('The array must have the items with the specified keys'));
        Must::haveAtLeastKeys($actual, $requiredKeys);
    }

    public static function dataHaveAtLeastKeys_Valid(): iterable {
        return [
            [
                ['foo' => 1, 'bar' => 2],
                ['foo', 'bar'],
            ],
            [
                ['foo' => 1, 'bar' => 2, 'baz' => 3],
                ['foo', 'bar'],
            ],
            [
                [],
                [],
            ],
        ];
    }

    #[DataProvider('dataHaveAtLeastKeys_Valid')]
    public function testHaveAtLeastKeys_Valid($actual, $requiredKeys) {
        Must::haveAtLeastKeys($actual, $requiredKeys);
        $this->markTestAsNotRisky();
    }

    public static function dataHaveExactKeys_Invalid(): iterable {
        return [
            [
                ['foo' => '1', 'something' => 2],
                ['foo', 'bar', 'baz'], // less
            ],
            [
                ['foo' => '2', 'bar' => 2, 'baz' => 3, 'something' => 4],
                ['foo', 'bar', 'baz'], // more
            ],
            [
                ['foo' => '1', 'bar' => 2, 'baz' => 3],
                ['foo', 'baz', 'bar'], // Order does matter
            ],
        ];
    }

    #[DataProvider('dataHaveExactKeys_Invalid')]
    public function testHaveExactKeys_Invalid($val, $keys) {
        $this->expectExceptionObject(new MustException('The array must have the items with the specified keys and no other items'));
        Must::haveExactKeys($val, $keys);
    }

    public static function dataHaveExactKeys_Valid(): iterable {
        return [
            [
                ['foo' => '1', 'bar' => 2, 'baz' => 3],
                ['foo', 'bar', 'baz'],
            ],
            [
                ['foo' => 1],
                ['foo'],
            ],
            [
                [],
                [],
            ],
        ];
    }

    #[DataProvider('dataHaveExactKeys_Valid')]
    public function testHaveExactKeys_Valid(...$args) {
        Must::haveExactKeys(...$args);
        $this->markTestAsNotRisky();
    }
}
