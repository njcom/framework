<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Uri;

use Morpho\Testing\TestCase;
use Morpho\Uri\IUriComponent;
use Morpho\Uri\Path;
use RuntimeException;
use Morpho\Test\Unit\Base\PathTest as BasePathTest;

use function rawurlencode;

class PathTest extends TestCase {
    public function testInitialState() {
        $path = new Path('');
        $this->assertSame('', $path->toStr(false));
        $this->assertNull($path->basePath());
        $this->assertNull($path->relPath());
    }

    public function testInterface() {
        $this->assertInstanceOf(IUriComponent::class, new Path('test'));
    }

    public function testToStr_Encode() {
        $pathComp1 = 'это';
        $pathComp2 = 'тест';
        $path = new Path($pathComp1 . '/' . $pathComp2);
        $this->assertSame(
            rawurlencode($pathComp1) . '/' . rawurlencode($pathComp2),
            $path->toStr(true)
        );
    }

    public static function dataBasePathAccessors() {
        yield [
            '/base/path/foo/bar',
            '/base/path',
            'foo/bar',
        ];
        yield [
            '',
            '',
            '',
        ];
    }

    /**
     * @dataProvider dataBasePathAccessors
     */
    public function testBasePathAccessors(string $uri, string $basePathStr, string $relPathStr) {
        $path = new Path($uri);
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertNull($path->setBasePath($basePathStr));
        $this->assertSame($basePathStr, $path->basePath());
        $this->assertSame($relPathStr, $path->relPath());
    }

    public function testThrowsExIfPathDoesNotStartWithBasePath() {
        $path = new Path('/foo/bar/baz');
        $this->expectException(RuntimeException::class, 'The base path is not at beginning of the path');
        $path->setBasePath('/base/path');
    }

    public static function dataIsRel() {
        yield ['', true];
        yield ['/', false];
        yield ['//', false];
        yield ['foo/bar', true];
        yield ['/foo/bar', false];
        yield ['//foo/bar', false];
        yield ['./foo/bar', true];
        yield ['../foo/bar', true];
        yield ['/../foo/bar', false];
        yield ['.', true];
    }

    /**
     * @dataProvider dataIsRel
     */
    public function testIsRel(string $pathStr, bool $isRel) {
        $path = new Path($pathStr);
        $isRel ? $this->assertTrue($path->isRel()) : $this->assertFalse($path->isRel());
    }

    public static function dataCombine() {
        yield from (new BasePathTest(__METHOD__))->dataCombine();
        yield from [
            [
                '//', '//', '/',
            ],
            [
                '//example.local', '//', 'example.local',
            ],
            [
                '//example.local/foo', '//example.local', 'foo',
            ],
            [
                'http://foo/bar',
                'http://foo/',
                '/',
                '/bar',
            ],
            [
                'http://localhost/foo',
                'http://localhost',
                '/foo',
            ],
            [
                'http://localhost/foo/bar/',
                'http://localhost/foo/',
                '/bar/',
            ],
            [
                'http://localhost/foo',
                'http://localhost',
                'foo',
            ],
            [
                'https://localhost/foo/bar/baz',
                'https://localhost',
                'foo',
                '/bar/baz',
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
