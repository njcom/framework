<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Uri;

use Morpho\Uri\Authority;
use Morpho\Uri\Path;
use Morpho\Uri\Query;
use Morpho\Uri\Uri;
use Morpho\Testing\TestCase;

use function rawurlencode;
use function str_replace;

class UriTest extends TestCase {
    use TUriParserDataProvider;

    public function testToStr_Encode() {
        // We use schema in not the RFC 3986 format (ALPHA *( ALPHA / DIGIT / "+" / "-" / "." )) intentionally
        $uriStr = "схема://юзер:пароль@хост:1234/базовый/путь/тест?один=единица&два=двойка#фрагмент";
        $this->assertSame(
            rawurlencode('схема')
            . '://'
            . rawurlencode('юзер') . ':' . rawurlencode('пароль')
            . '@'
            . rawurlencode('хост')
            . ':1234'
            . str_replace('%2F', '/', rawurlencode('/базовый/путь/тест'))
            . '?'
            . rawurlencode('один') . '=' . rawurlencode('единица') . '&' . rawurlencode('два') . '=' . rawurlencode(
                'двойка'
            )
            . '#' . rawurlencode('фрагмент'),
            (new Uri($uriStr))->toStr(null, true)
        );
    }

    public function testToStr_ConfForParts() {
        $scheme = 'http';
        $authority = 'example.com';
        $path = '/foo/bar';
        $query = 'one=1&two=2';
        $fragment = 'toc';
        $uriStr = $scheme . '://' . $authority . $path . '?' . $query . '#' . $fragment;
        $uri = new Uri($uriStr);

        // Scheme
        $this->assertSame($scheme, $uri->toStr(['scheme'], false));

        // Authority
        $this->assertSame($authority, $uri->toStr(['authority'], false));
        $this->assertSame($scheme . '://' . $authority, $uri->toStr(['scheme', 'authority'], false));

        // Path
        $this->assertSame($path, $uri->toStr(['path'], false));
        $this->assertSame($scheme . '://' . $authority . $path, $uri->toStr(['scheme', 'authority', 'path'], false));
        $this->assertSame('//' . $authority . $path, $uri->toStr(['authority', 'path'], false));

        // Query
        $this->assertSame($query, $uri->toStr(['query'], false));
        $this->assertSame($path . '?' . $query, $uri->toStr(['path', 'query'], false));
        $this->assertSame('//' . $authority . $path . '?' . $query, $uri->toStr(['authority', 'path', 'query'], false));
        $this->assertSame(
            $scheme . '://' . $authority . $path . '?' . $query,
            $uri->toStr(['scheme', 'authority', 'path', 'query'], false)
        );
        $this->assertSame('//' . $authority . '?' . $query, $uri->toStr(['authority', 'query'], false));

        // Fragment
        $this->assertSame($fragment, $uri->toStr(['fragment'], false));
        $this->assertSame('?' . $query . '#' . $fragment, $uri->toStr(['query', 'fragment'], false));
        $this->assertSame($path . '?' . $query . '#' . $fragment, $uri->toStr(['path', 'query', 'fragment'], false));
        $this->assertSame($path . '#' . $fragment, $uri->toStr(['path', 'fragment'], false));
    }

    public function testSchemeAccessors() {
        $this->checkAccessors([new Uri(), 'scheme'], '', 'http');
        $this->checkAccessors([new Uri(), 'scheme'], '', 'http');
    }

    public function testAuthorityAccessors() {
        $uri = new Uri();

        $authority = $uri->authority();
        $this->assertEquals(new Authority(), $authority);
        $this->assertTrue($authority->isNull());

        $newAuthority = new Authority('example.com');
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertVoid($uri->setAuthority($newAuthority));
        $this->assertSame($newAuthority, $uri->authority());
    }

    public function testPathAccessors() {
        $uri = new Uri();

        $this->assertEquals(new Path(''), $uri->path());

        $path = '/foo/bar';
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertVoid($uri->setPath($path));
        $this->assertEquals(new Path($path), $uri->path());

        $path = new Path($path);
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertVoid($uri->setPath($path));
        $this->assertSame($path, $uri->path());
    }

    public function testQueryAccessors() {
        $uri = new Uri();

        $query = $uri->query();
        $this->assertEquals(new Query(), $query);
        $this->assertTrue($query->isNull());

        $newQuery = new Query('foo=bar&test=123');
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertNull($uri->setQuery($newQuery));
        $this->assertSame($newQuery, $uri->query());
    }

    public function testFragmentAccessors() {
        $this->checkAccessors([new Uri(), 'fragment'], null, 'toc');
        $this->checkAccessors([new Uri(), 'fragment'], null, null);
    }

    public function testParse_UriInstance() {
        $uri = new Uri();
        $this->assertSame($uri, Uri::parse($uri));
    }

    public static function dataToStr(): iterable {
        foreach (self::dataParse() as $sample) {
            yield [$sample[0]];
        }
    }

    /**
     * @dataProvider dataToStr
     */
    public function testToStr(string $uriStr) {
        $uri = new Uri($uriStr);
        $this->assertSame($uriStr, $uri->toStr(null, false));
    }

    public static function dataResolveRelUri_NormalExamples() {
        yield ['g:h', 'g:h'];
        yield ['g', 'http://a/b/c/g'];
        yield ['./g', 'http://a/b/c/g'];
        yield ['g/', 'http://a/b/c/g/'];
        yield ['/g', 'http://a/g'];
        yield ['//g', 'http://g'];
        yield ['?y', 'http://a/b/c/d;p?y'];
        yield ['g?y', 'http://a/b/c/g?y'];
        yield ['#s', 'http://a/b/c/d;p?q#s'];
        yield ['g#s', 'http://a/b/c/g#s'];
        yield ['g?y#s', 'http://a/b/c/g?y#s'];
        yield [';x', 'http://a/b/c/;x'];
        yield ['g;x', 'http://a/b/c/g;x'];
        yield ['g;x?y#s', 'http://a/b/c/g;x?y#s'];
        yield ['', 'http://a/b/c/d;p?q'];
        yield ['.', 'http://a/b/c/'];
        yield ['./', 'http://a/b/c/'];
        yield ['..', 'http://a/b/'];
        yield ['../', 'http://a/b/'];
        yield ['../g', 'http://a/b/g'];
        yield ['../..', 'http://a/'];
        yield ['../../', 'http://a/'];
        yield ['../../g', 'http://a/g'];

        yield ['http://foo/bar', 'http://foo/bar'];
    }

    /**
     * @dataProvider dataResolveRelUri_NormalExamples
     */
    public function testResolveRelUri_NormalExamples($relUri, $expected) {
        $uri = Uri::resolveRelUri('http://a/b/c/d;p?q', $relUri);
        $this->assertSame($expected, $uri->toStr(null, false));
    }

    public static function dataResolveRelUri_AbnormalExamples() {
        yield ['../../../g', 'http://a/g'];
        yield ['../../../../g', 'http://a/g'];
        yield ['/./g', 'http://a/g'];
        yield ['/../g', 'http://a/g'];
        yield ['g.', 'http://a/b/c/g.'];
        yield ['.g', 'http://a/b/c/.g'];
        yield ['g..', 'http://a/b/c/g..'];
        yield ['..g', 'http://a/b/c/..g'];
        yield ['./../g', 'http://a/b/g'];
        yield ['./g/.', 'http://a/b/c/g/'];
        yield ['g/./h', 'http://a/b/c/g/h'];
        yield ['g/../h', 'http://a/b/c/h'];
        yield ['g;x, 1/./y', 'http://a/b/c/g;x, 1/y'];
        yield ['g;x, 1/../y', 'http://a/b/c/y'];
        yield ['g?y/./x', 'http://a/b/c/g?y/./x'];
        yield ['g?y/../x', 'http://a/b/c/g?y/../x'];
        yield ['g#s/./x', 'http://a/b/c/g#s/./x'];
        yield ['g#s/../x', 'http://a/b/c/g#s/../x'];
        yield ['http:g', 'http:g'];
    }

    /**
     * @dataProvider dataResolveRelUri_AbnormalExamples
     */
    public function testResolveRelUri_AbnormalExamples($relUri, $expected) {
        $uri = Uri::resolveRelUri('http://a/b/c/d;p?q', $relUri);
        $this->assertSame($expected, $uri->toStr(null, false));
    }
}
