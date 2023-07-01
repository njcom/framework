<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\HttpMethod;
use Morpho\App\Web\IRequest;
use Morpho\App\Web\Request;
use Morpho\Uri\Uri;
use Morpho\Testing\TestCase;

use Traversable;

use function array_merge;
use function rawurlencode;

class RequestTest extends TestCase {
    private IRequest $request;
    private array $serverVars;

    protected function setUp(): void {
        parent::setUp();
        $_GET = $_POST = $_REQUEST = $_COOKIE = [];
        $this->serverVars = $_SERVER;
        $this->request = $this->mkRequest([]);
    }

    protected function tearDown(): void {
        parent::tearDown();
        $_SERVER = $this->serverVars;
    }

    public function testResponse_ReturnsTheSameInstance() {
        $response = $this->request->response();
        $this->assertSame($response, $this->request->response());
    }

    public function testIsAjax_BoolAccessor() {
        $this->checkBoolAccessor([$this->request, 'isAjax'], false);
    }

    public function testIsAjax_ByDefaultReturnsValueFromHeaders() {
        $this->request->headers()['X-Requested-With'] = 'XMLHttpRequest';
        $this->assertTrue($this->request->isAjax());
        $this->request->headers()->exchangeArray([]);
        $this->assertFalse($this->request->isAjax());
    }

    public static function dataSettingHeadersThroughServerVars() {
        yield [true];
        yield [false];
    }

    /**
     * @dataProvider dataSettingHeadersThroughServerVars
     */
    public function testSettingHeadersThroughServerVars($useGlobalServerVar) {
        $serverVars = [
            "HOME"                           => "/foo/bar",
            "USER"                           => "user-name",
            "HTTP_CACHE_CONTROL"             => "max-age=0",
            "HTTP_CONNECTION"                => "keep-alive",
            "HTTP_UPGRADE_INSECURE_REQUESTS" => "1",
            "HTTP_COOKIE"                    => "TestCookie=something+from+somewhere",
            "HTTP_ACCEPT_LANGUAGE"           => "en-US,en;q=0.5",
            "HTTP_ACCEPT_ENCODING"           => "gzip, deflate",
            "HTTP_USER_AGENT"                => "Test user agent",
            "REDIRECT_STATUS"                => "200",
            "HTTP_HOST"                      => "localhost",
            "SERVER_NAME"                    => "localhost",
            "SERVER_ADDR"                    => "127.0.0.1",
            "HTTP_FOO"                       => "Bar",
            "SERVER_PORT"                    => "80",
            "REMOTE_PORT"                    => "12345",
            "HTTP_ACCEPT"                    => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "SCRIPT_NAME"                    => "/test.php",
            "CONTENT_LENGTH"                 => "4521",
            "CONTENT_TYPE"                   => "",
            "REQUEST_METHOD"                 => HttpMethod::Post->value,
            "CONTENT_MD5"                    => "Q2hlY2sgSW50ZWdyaXR5IQ==",
        ];
        $expectedHeaders = [
            'Cache-Control'             => $serverVars['HTTP_CACHE_CONTROL'],
            'Connection'                => $serverVars['HTTP_CONNECTION'],
            'Upgrade-Insecure-Requests' => $serverVars['HTTP_UPGRADE_INSECURE_REQUESTS'],
            'Accept-Language'           => $serverVars['HTTP_ACCEPT_LANGUAGE'],
            'Accept-Encoding'           => $serverVars['HTTP_ACCEPT_ENCODING'],
            'User-Agent'                => $serverVars['HTTP_USER_AGENT'],
            'Host'                      => $serverVars['HTTP_HOST'],
            'Foo'                       => $serverVars['HTTP_FOO'],
            'Accept'                    => $serverVars['HTTP_ACCEPT'],
            'Content-Length'            => $serverVars['CONTENT_LENGTH'],
            'Content-Type'              => $serverVars['CONTENT_TYPE'],
            'Content-MD5'               => $serverVars['CONTENT_MD5'],
        ];
        if ($useGlobalServerVar) {
            $_SERVER = $serverVars;
            $request = new Request();
        } else {
            $request = new Request(null, $serverVars);
        }
        $this->assertSame($expectedHeaders, $request->headers()->getArrayCopy());
    }

    public function testHeadersAccessors() {
        $this->assertSame([], $this->request->headers()->getArrayCopy());
        $this->request->headers()['foo'] = 'bar';
        $this->assertSame('bar', $this->request->headers()['foo']);
        $this->assertSame(['foo' => 'bar'], $this->request->headers()->getArrayCopy());
    }

    public function testHandlerAccessors() {
        $handler = ['foo', 'bar', 'baz'];
        $this->request->setHandler($handler);
        $this->assertEquals($handler, $this->request->handler());
    }

    public function testHasQuery() {
        $this->assertFalse($this->request->hasQuery('some'));
        $_GET['some'] = 'ok';
        $this->assertTrue($this->request->hasQuery('some'));
    }

    public function testHasPost() {
        $this->assertFalse($this->request->hasPost('some'));
        $_POST['some'] = 'ok';
        $this->assertTrue($this->request->hasPost('some'));
    }

    public function testUri_HasValidComponents() {
        $trustedProxyIp = '127.0.0.3';
        $this->request->setTrustedProxyIps([$trustedProxyIp]);
        $this->request->setServerVars([
            'REMOTE_ADDR' => $trustedProxyIp,
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_HOST' => 'blog.example.com:8042',
            'REQUEST_URI' => '/top.htm?page=news&skip=10',
            'QUERY_STRING' => 'page=news&skip=10',
            'SCRIPT_NAME' => '/',
        ]);
        $uri = $this->request->uri();
        $this->assertEquals('https://blog.example.com:8042/top.htm?page=news&skip=10', $uri->toStr(null, true));
    }

    public static function dataIsHttpMethod(): Traversable {
        foreach (HttpMethod::cases() as $httpMethod) {
            yield [$httpMethod];
        }
    }

    /**
     * @dataProvider dataIsHttpMethod
     */
    public function testIsHttpMethod(HttpMethod $httpMethod) {
        $_SERVER['REQUEST_METHOD'] = 'unknown';
        if ($httpMethod === HttpMethod::Get) {
            $this->assertTrue($this->request->isGetMethod());
        } else {
            $this->assertFalse($this->request->{'is' . $httpMethod->value . 'Method'}());
        }
        $this->request->setMethod($httpMethod);
        $this->assertTrue($this->request->{'is' . $httpMethod->value . 'Method'}());
    }

    public function testIsHandled() {
        $this->checkBoolAccessor([$this->request, 'isHandled'], false);
    }

    public function testTrim_Query() {
        $val = '   baz  ';
        $_GET['foo']['bar'] = $val;
        $this->assertEquals('baz', $this->request->query('foo')['bar']);
        $this->assertEquals($val, $this->request->query('foo', false)['bar']);
    }

    public function testTrim_Post() {
        $val = '   baz  ';
        $_POST['foo']['bar'] = $val;
        $this->assertEquals('baz', $this->request->post('foo')['bar']);
        $this->assertEquals($val, $this->request->post('foo', false)['bar']);
    }

    public function testDoesNotChangeGlobals() {
        $_GET['foo'] = ['one' => 1];

        $v = $this->request->query('foo');
        $v['one'] = 2;

        $this->assertEquals(['one' => 1], $_GET['foo']);
    }

    public function testGetGet_ReturnsNullWhenNotSet() {
        $this->assertNull($this->request->query('foo', true));
        $this->assertNull($this->request->query('foo', false));
    }

    public static function dataGetArgs() {
        yield [HttpMethod::Get];
        yield [HttpMethod::Post];
    }

    /**
     * @dataProvider dataGetArgs
     */
    public function testArgs(HttpMethod $httpMethod) {
        // @TODO: Test patch, put
        $this->request->setMethod($httpMethod);

        // Write to $_GET | $_POST
        $GLOBALS['_' . $httpMethod->value]['foo']['bar'] = 'baz';

        $this->assertEquals(
            ['non' => null, 'foo' => ['bar' => 'baz']],
            $this->request->args(['foo', 'non'])
        );
    }

    public function testUriInitialization_BasePath() {
        $basePath = '/foo/bar/baz';
        $request = new Request(null,
            [
                'REQUEST_URI' => $basePath . '/index.php/one/two',
                'SCRIPT_NAME' => $basePath . '/index.php',
            ]
        );
        $uri = $request->uri();
        $this->assertSame($basePath, $uri->path()->basePath());
    }

    public static function dataPrependWithBasePath() {
        yield [
            '/foo/news/',
            '/foo',
            '/foo',
            '/news/',
        ];
        yield [
            '',
            null,
            '',
            '',
        ];
        yield [
            '/',
            '/',
            '/',
            '/',
        ];
        yield [
            '',
            null,
            '/',
            '',
        ];
        yield [
            '/foo/bar/baz/abc?test=123&redirect=' . rawurlencode(
                'http://localhost/some/base/path/abc/def?three=qux&four=pizza'
            ) . '#toc',
            '/foo/bar',
            '/foo/bar',
            '/baz/abc?test=123&redirect=' . rawurlencode(
                'http://localhost/some/base/path/abc/def?three=qux&four=pizza'
            ) . '#toc',
        ];
        yield [
            '/foo/bar/abc/def/ghi',
            '/foo/bar',
            '/foo/bar',
            '/abc/def/ghi', // starts with `/` => prepend
        ];
        yield [
            'abc/def/ghi',
            null,
            '/foo/bar',
            'abc/def/ghi', // doesn't start with `/` => don't prepend
        ];
        yield [
            '/foo/bar',
            '/foo/bar',
            '/foo/bar',
            '/', // starts with '/` => prepend
        ];
        yield [
            '/foo/bar',
            '/',
            '/',
            '/foo/bar', // starts with '/` => prepend
        ];
        yield [
            '/foo/bar',
            '/',
            '/',
            '/foo/bar', // starts with '/` => prepend
        ];
    }

    /**
     * @dataProvider dataPrependWithBasePath
     */
    public function testPrependWithBasePath($expectedUri, $expectedBasePath, $basePath, $pathToPrepend) {
        $fullRequestUri = 'http://localhost/foo/bar/baz';
        $uri = new Uri($fullRequestUri);
        $uri->path()->setBasePath($basePath);
        $this->request->setUri($uri);
        $this->assertSame($basePath, $this->request->uri()->path()->basePath());

        $prepended = $this->request->prependWithBasePath($pathToPrepend);

        $this->assertSame($expectedBasePath, $prepended->path()->basePath());
        $this->assertSame($expectedUri, $prepended->toStr(null, false));
    }

    public static function dataUriInitialization_Scheme() {
        yield [false, []];
        yield [true, ['HTTPS' => 'on']];
        yield [false, ['HTTPS' => 'off']];
        yield [false, ['HTTPS' => 'OFF']];
        yield [true, ['HTTP_X_FORWARDED_PROTO' => 'https']];
        yield [true, ['HTTP_X_FORWARDED_PROTO' => 'on']];
        yield [false, ['HTTP_X_FORWARDED_PROTO' => 'off']];
        yield [false, ['HTTP_X_FORWARDED_PROTO' => 'OFF']];
        yield [true, ['HTTP_X_FORWARDED_PROTO' => 'ssl']];
        yield [true, ['HTTP_X_FORWARDED_PROTO' => '1']];
        yield [false, ['HTTP_X_FORWARDED_PROTO' => '']];
    }

    /**
     * @dataProvider dataUriInitialization_Scheme
     */
    public function testUriInitialization_Scheme($isHttps, $serverVars) {
        $trustedProxyIp = '127.0.0.2';
        $serverVars['REMOTE_ADDR'] = $trustedProxyIp;
        $request = new Request(null, $serverVars);
        $request->setTrustedProxyIps([$trustedProxyIp]);
        if ($isHttps) {
            $this->assertSame('https', $request->uri()->scheme());
        } else {
            $this->assertSame('http', $request->uri()->scheme());
        }
    }

    public function testUriInitialization_Query() {
        $request = new Request(null,
            [
                'REQUEST_URI'  => '/',
                'SCRIPT_NAME'  => '/index.php',
                'QUERY_STRING' => '',
                'HTTP_HOST'    => 'framework',
            ]
        );
        $uri = $request->uri();
        $this->assertSame('http://framework/', $uri->toStr(null, true));
    }

    public function testData() {
        $this->assertSame(
            ['bar' => 'baz'],
            $this->request->data(['foo' => ['bar' => ' baz  ']], 'foo')
        );
    }

    public function testMappingPostToPatch() {
        $data = ['foo' => 'bar', 'baz' => 'abc'];
        $_POST = array_merge($data, ['_method' => HttpMethod::Patch->value]);
        $request = new Request();
        $this->assertTrue($request->isPatchMethod());
        $this->assertSame($data, $request->patch());
    }

    public function testIsKnownMethod() {
        foreach ($this->request->knownMethods() as $method) {
            $this->assertTrue($this->request->isKnownMethod($method));
        }
        $this->assertFalse($this->request->isKnownMethod('unknown'));
    }

    public static function dataMethod(): Traversable {
        foreach (HttpMethod::cases() as $httpMethod) {
            yield [$httpMethod];
        }
    }

    /**
     * @dataProvider dataMethod
     */
    public function testMethod_OverwritingHttpMethod_ThroughMethodArg(HttpMethod $httpMethod) {
        $_GET['_method'] = $httpMethod->value;
        $this->checkHttpMethod(['REQUEST_METHOD' => HttpMethod::Post->value], $httpMethod);
    }

    private function checkHttpMethod(array $serverVars, HttpMethod $httpMethod): void {
        $request = new Request(null, $serverVars);
        $this->assertSame($httpMethod, $request->method());
        $this->assertTrue($request->{'is' . $httpMethod->value . 'Method'}());
    }

    /**
     * @dataProvider dataMethod
     */
    public function testMethod_OverwritingHttpMethod_ThroughHeader(HttpMethod $httpMethod) {
        $this->checkHttpMethod(
            [
                'REQUEST_METHOD'              => HttpMethod::Post->value,
                'HTTP_X_HTTP_METHOD_OVERRIDE' => $httpMethod->value,
            ],
            $httpMethod
        );
    }

    /**
     * @dataProvider dataMethod
     */
    public function testMethod_Default(HttpMethod $httpMethod) {
        $this->checkHttpMethod(['REQUEST_METHOD' => $httpMethod->value], $httpMethod);
    }

    private function mkRequest(array $serverVars): IRequest {
        return new Request(null, $serverVars);
    }
}
