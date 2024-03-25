<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\IMessage;
use Morpho\App\Web\HttpMethod;
use Morpho\App\Web\IRequest;
use Morpho\App\Web\Request;
use Morpho\Test\Unit\App\MessageTest;
use Morpho\Uri\Uri;

use PHPUnit\Framework\Attributes\DataProvider;

use function rawurlencode;

class RequestTest extends MessageTest {
    private IRequest $request;
    private array $serverVars;

    protected function setUp(): void {
        parent::setUp();
        $_GET = $_POST = $_REQUEST = $_COOKIE = [];
        $this->serverVars = $_SERVER;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/';
        $this->request = new Request();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $_SERVER = $this->serverVars;
    }

/*    public function testResponse_ReturnsTheSameInstance() {
        $response = $this->request->response();
        $this->assertSame($response, $this->request->response());
    }*/

    public function testIsAjax_BoolAccessor() {
        $this->checkBoolAccessor([$this->request, 'isAjax'], false);
    }

    public function testIsAjax_ByDefaultReturnsValueFromHeaders() {
        $this->request->headers['X-Requested-With'] = 'XMLHttpRequest';
        $this->assertTrue($this->request->isAjax());
        $this->request->headers->exchangeArray([]);
        $this->assertFalse($this->request->isAjax());
    }

    public function testSettingHeadersThroughServerVars() {
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
        $_SERVER = array_merge($_SERVER, $serverVars);
        $request = new Request();
        $this->assertSame($expectedHeaders, $request->headers->getArrayCopy());
    }

    public function testHeadersAccessors() {
        $this->assertSame([], $this->request->headers->getArrayCopy());
        $this->request->headers['foo'] = 'bar';
        $this->assertSame('bar', $this->request->headers['foo']);
        $this->assertSame(['foo' => 'bar'], $this->request->headers->getArrayCopy());
    }

    public function testHandlerAccessors() {
        $handler = ['foo', 'bar', 'baz'];
        $this->request->handler = $handler;
        $this->assertEquals($handler, $this->request->handler);
    }

    public function testUri_HasValidComponents() {
        $trustedProxyIp = '127.0.0.3';
        $_SERVER = array_merge($_SERVER, [
            'REMOTE_ADDR' => $trustedProxyIp,
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_HOST' => 'blog.example.com:8042',
            'REQUEST_URI' => '/top.htm?page=news&skip=10',
            'QUERY_STRING' => 'page=news&skip=10',
            'SCRIPT_NAME' => '/',
        ]);
        $request = new Request(trustedProxyIps: [$trustedProxyIp]);
        $uri = $request->uri;
        $this->assertEquals('https://blog.example.com:8042/top.htm?page=news&skip=10', $uri->toStr(null, true));
    }

    public static function dataHttpMethod(): iterable {
        return array_map(fn ($v) => [$v], HttpMethod::cases());
    }

    #[DataProvider('dataHttpMethod')]
    public function testHttpMethod(HttpMethod $httpMethod) {
        $_SERVER['REQUEST_METHOD'] = $httpMethod->value;
        $request = new Request();
        $this->assertSame($httpMethod, $request->httpMethod);
    }

    /**
     * dataProvider dataMethodAccessors
    public function testMethod_OverwritingHttpMethod_ThroughMethodArg(HttpMethod $httpMethod) {
        $_GET['_method'] = $httpMethod->value;
        $this->checkHttpMethod(['REQUEST_METHOD' => HttpMethod::Post->value], $httpMethod);
    }
*/

    /**
     * dataProvider dataMethodAccessors
    public function testMethod_OverwritingHttpMethod_ThroughHeader(HttpMethod $httpMethod) {
        $this->checkHttpMethod(
            [
                'REQUEST_METHOD'              => HttpMethod::Post->value,
                'HTTP_X_HTTP_METHOD_OVERRIDE' => $httpMethod->value,
            ],
            $httpMethod
        );
    }
    */

    public function testUriInitialization_BasePath() {
        $basePath = '/foo/bar/baz';
        $_SERVER = array_merge($_SERVER, [
            'REQUEST_URI' => $basePath . '/index.php/one/two',
            'SCRIPT_NAME' => $basePath . '/index.php',
        ]);
        $request = new Request();
        $this->assertSame($basePath, $request->uri->path()->basePath());
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

    #[DataProvider('dataPrependWithBasePath')]
    public function testPrependWithBasePath($expectedUri, $expectedBasePath, $basePath, $pathToPrepend) {
        $fullRequestUri = 'http://localhost/foo/bar/baz';
        $uri = new Uri($fullRequestUri);
        $uri->path()->setBasePath($basePath);
        $this->request->uri = $uri;
        $this->assertSame($basePath, $this->request->uri->path()->basePath());

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

    #[DataProvider('dataUriInitialization_Scheme')]
    public function testUriInitialization_Scheme($isHttps, $serverVars) {
        $trustedProxyIp = '127.0.0.2';
        $_SERVER = array_merge($_SERVER, $serverVars);
        $_SERVER['REMOTE_ADDR'] = $trustedProxyIp;
        $request = new Request(trustedProxyIps: [$trustedProxyIp]);
        if ($isHttps) {
            $this->assertSame('https', $request->uri->scheme());
        } else {
            $this->assertSame('http', $request->uri->scheme());
        }
    }

    public function testUriInitialization_Query() {
        $_SERVER = array_merge($_SERVER, [
            'REQUEST_URI'  => '/',
            'SCRIPT_NAME'  => '/index.php',
            'QUERY_STRING' => '',
            'HTTP_HOST'    => 'framework',
        ]);
        $request = new Request();
        $this->assertSame('http://framework/', $request->uri->toStr(null, true));
    }

    protected function mkMessage(): IMessage {
        return clone $this->request;
    }
}
