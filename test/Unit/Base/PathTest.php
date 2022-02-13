<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use Morpho\Base\Path;
use Morpho\Testing\TestCase;
use RuntimeException;

class PathTest extends TestCase {
    public function dataNormalize() {
        return [
            ['', ''],
            ['/', '/'],
            ['/', '\\'],
            ['/foobar', '/foobar/'],
            ['/foobar', '/foobar\\'],
            ['foo/bar/baz', 'foo\\bar\\baz/'],
            ['/foo/bar/baz', '/foo\\bar\\baz/'],
            ['foo/baz', 'foo\\..\\baz/'],
            ['/foo/bar/setosa/versicolor', '/foo/bar/baz/../setosa/versicolor'],
            // These 2 samples taken from the https://github.com/zendframework/zend-uri/blob/master/test/UriTest.php:
            ['/a/g', '/a/b/c/./../../g'],
            ['mid/6', 'mid/content=5/../6'],
        ];
    }

    /**
     * @dataProvider dataNormalize
     */
    public function testNormalize(string $expected, string $path) {
        $this->assertSame($expected, Path::normalize($path));
    }

    public function testRel() {
        $baseDirPath = __DIR__ . '/../../..';
        $this->assertEquals(Path::rel($baseDirPath, $baseDirPath . '/module/foo/bar'), 'module/foo/bar');
        $this->assertSame(Path::rel($baseDirPath, $baseDirPath), '');
        $this->assertSame(Path::rel($baseDirPath, $baseDirPath . '/'), '');
        $this->assertSame(Path::rel($baseDirPath, $baseDirPath . '/index.php'), 'index.php');
    }

    public function testRel_ThrowsExceptionWhenBasePathNotContainedWithinPath() {
        $baseDirPath = '/foo/bar/baz/';
        $path = __DIR__;
        $this->expectException(
            RuntimeException::class,
            "The path '" . str_replace('\\', '/', $path) . "' does not contain the base path '/foo/bar/baz'"
        );
        Path::rel($baseDirPath, $path);
    }

    public function dataCombine() {
        return [
            [
                '',
                '',
                '',
            ],
            [
                '/', '/', '', null
            ],
            [
                '', null, '', null
            ],
            [
                '/',
                '',
                '',
                '/',
            ],
            [
                '/',
                '',
                '/',
            ],
            [
                '/',
                '/',
                '',
            ],
            [
                '/',
                '/',
                '/',
            ],
            [
                'foo/bar/baz',
                ['foo', 'bar', null, 'baz'], // array syntax
            ],
            [
                'bar',
                '',
                'bar',
                '',
            ],
            [
                'foo/bar',
                '',
                'foo',
                'bar',
            ],
            [
                'foo/bar/',
                '',
                'foo',
                'bar/',
            ],
            [
                '/foo',
                '/foo',
                '/',
                '/',
            ],
            [
                '/foo/bar/',
                '/foo',
                '/',
                '/bar/',
            ],
            [
                '/foo/bar/',
                '/foo/',
                '/',
                '/bar/',
            ],
            [
                '/foo/bar',
                '/foo',
                '/',
                '/bar',
            ],
            [
                'foo\\bar/baz',
                'foo\\bar',
                'baz',
            ],
            [
                'foo\\bar/baz',
                'foo\\bar',
                '/baz',
            ],
            [
                '/foo\\bar/baz',
                '/foo\\bar',
                'baz',
            ],
            [
                '/foo\\bar/baz',
                '/foo\\bar',
                '/baz',
            ],
            [
                'foo\\bar/baz',
                'foo\\bar/',
                '/baz',
            ],
            [
                '/foo\\bar/baz',
                '/foo\\bar/',
                '/baz',
            ],
            [
                'foo\\bar/baz/',
                'foo\\bar',
                'baz/',
            ],
            [
                'foo\\bar/baz/',
                'foo\\bar',
                '/baz/',
            ],
            [
                '/foo\\bar/baz/',
                '/foo\\bar',
                'baz/',
            ],
            [
                '/foo\\bar/baz/',
                '/foo\\bar',
                '/baz/',
            ],
            [
                'foo\\bar/baz/',
                'foo\\bar/',
                '/baz/',
            ],
            [
                '/foo\\bar/baz/',
                '/foo\\bar/',
                '/baz/',
            ],
            [
                '/foo/bar', '/', '/foo', '/bar'
            ],
            [
                '/foo/bar', '/', 'foo', 'bar'
            ],
            [
                '/foo/bar', '', '/foo/bar'
            ],
        ];
    }

    /**
     * @dataProvider dataCombine
     */
    public function testCombine(string $expected, ...$paths) {
        $this->assertSame($expected, Path::combine(...$paths));
    }
}
