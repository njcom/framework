<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Caching;

use ArrayIterator;
use Morpho\Caching\VarExportFileCache;
use Morpho\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use stdClass;

use function get_debug_type;
use function is_object;

class VarExportFileCacheTest extends TestCase {
    public static function dataCaching() {
        yield [
            ['foo' => 'bar'],
        ];
        yield [
            false,
        ];
        yield [
            true,
        ];
        yield [
            null,
        ];
        yield [
            'Hello World',
        ];
        yield [
            3.14,
        ];
        yield [
            [3 => 456, 1 => 'abc'],
        ];
    }

    #[DataProvider('dataCaching')]
    public function testCaching($data) {
        $cache = new VarExportFileCache($this->createTmpDir());

        $key = 'my-val';
        $this->assertFalse($cache->has($key));
        $this->assertNull($cache->get($key));
        $this->assertSame('abc', $cache->get($key, 'abc'));
        $this->assertTrue($cache->set('my-val', $data));
        if (is_object($data)) {
            $this->assertEquals($data, $cache->get($key));
        } else {
            $this->assertSame($data, $cache->get($key));
        }
        $this->assertTrue($cache->delete($key));
        $this->assertFalse($cache->has($key));
        $this->assertNull($cache->get($key));
        $def = new stdClass();
        $this->assertSame($def, $cache->get($key, $def));
    }

    public static function dataThrowsExceptionOnNotSupportedDataType() {
        yield [new ArrayIterator([])];
        yield [STDIN];
    }

    #[DataProvider('dataThrowsExceptionOnNotSupportedDataType')]
    public function testThrowsExceptionOnNotSupportedDataType($data) {
        $cache = new VarExportFileCache($this->createTmpDir());
        $this->expectException(
            RuntimeException::class,
            'Only arrays and scalars are supported by this class, but $data has type ' . get_debug_type(
                $data
            )
        );
        $cache->set('foo', $data);
    }
}
