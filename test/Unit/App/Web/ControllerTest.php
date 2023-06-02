<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\ContentFormat;
use Morpho\App\Web\Controller;
use Morpho\App\Web\Request;
use Morpho\App\Web\Response;
use Morpho\Base\Ok;
use Morpho\Testing\TestCase;
use Morpho\Uri\Uri;

class ControllerTest extends TestCase {
    public function testReturnResultInstanceFromAction() {
        $val = 'test';

        $controller = new class ($val) extends Controller {
            public bool $called = false;
            private $val;

            public function __construct($val) {
                $this->val = $val;
            }

            public function someAction() {
                $this->called = true;
                return new Ok($this->val);
            }
        };
        $response = new Response();
        $request = $this->mkConfiguredRequest($response, 'http://example.local/');

        $request = $controller->__invoke($request);

        $this->assertTrue($controller->called);
        $result = $request->response()['result'];
        $this->assertEquals(new Ok($val), $result);
        $this->assertTrue($response->allowAjax());
        $this->assertSame([ContentFormat::JSON], $response->formats());
    }

    public static function dataRedirect() {
        yield [
            '/foo/bar',
            399,
            'http://example.local/',
            '/foo/bar',
            399,
            null,
        ];
        yield [
            'http://example.local/',
            Response::FOUND_STATUS_CODE,
            'http://example.local/',
            null,
            null,
            null,
        ];
        yield [
            'https://some.local/?',
            Response::FOUND_STATUS_CODE,
            'http://example.local',
            null,
            null,
            'https://some.local/?redirect=/bug',
        ];
        yield [
            'https://another.local/',
            Response::FOUND_STATUS_CODE,
            'http://example.local',
            null,
            null,
            'https://another.local/',
        ];
        yield [
            'http://framework/',
            Response::FOUND_STATUS_CODE,
            'http://example.local',
            null,
            null,
            'http%3A%2F%2Fframework%2F',
        ];
    }

    /**
     * @dataProvider dataRedirect
     */
    public function testRedirect(string $expectedLocation, int $expectedCode, string $currentUri, ?string $redirectUri, ?int $redirectCode, ?string $redirectQueryArg) {
        $response = new Response();
        $request = $this->mkConfiguredRequest($response, $currentUri);
        if (null !== $redirectQueryArg) {
            $request->uri()->query()['redirect'] = $redirectQueryArg;
        }
        $controller = new class ($redirectUri, $redirectCode) extends Controller {
            public function __construct(private ?string $redirectUri, private ?int $redirectCode) {
            }

            public function someAction() {
                return $this->redirect($this->redirectUri, $this->redirectCode);
            }
        };

        $request = $controller->__invoke($request);

        $changedResponse = $request->response();
        $this->assertSame($changedResponse, $response);
        $this->assertSame($expectedLocation, $changedResponse->headers()['Location']);
        $this->assertSame($expectedCode, $changedResponse->statusCode());
    }

    private function mkConfiguredRequest($response, string $uri) {
        $request = new Request();
        $uri = new Uri($uri);
        $request->setUri($uri);
        $request->setResponse($response);
        $request->setHandler(['method' => 'someAction']);
        return $request;
    }
}
