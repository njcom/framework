<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use ArrayObject;
use Morpho\App\Web\Env;
use Morpho\App\Web\Response;
use Morpho\Testing\TestCase;

use function array_merge;
use function func_get_args;
use function ob_get_clean;
use function ob_start;

class ResponseTest extends TestCase {
    private Response $response;

    protected function setUp(): void {
        parent::setUp();
        $this->response = new Response();
    }

    public function testStatusCodeAccessors() {
        $this->assertSame(Response::OK_STATUS_CODE, $this->response->statusCode());
        $newStatusCode = Response::FORBIDDEN_STATUS_CODE;
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertNull($this->response->setStatusCode($newStatusCode));
        $this->assertSame($newStatusCode, $this->response->statusCode());
    }

    public function testRedirect() {
        $this->assertFalse($this->response->isRedirect());
        $this->assertSame($this->response, $this->response->redirect('/foo/bar'));
        $this->assertTrue($this->response->isRedirect());
        $this->assertSame(Response::FOUND_STATUS_CODE, $this->response->statusCode());
    }

    public function testIsSuccess() {
        $this->assertTrue($this->response->isSuccess());
        $this->response->setStatusCode(Response::INTERNAL_SERVER_ERROR_STATUS_CODE);
        $this->assertFalse($this->response->isSuccess());
    }

    public function testStatusLineAccessors() {
        $this->assertSame(
            Env::httpProto() . ' ' . Response::OK_STATUS_CODE . ' OK',
            $this->response->statusLine()
        );
        $newStatusLine = Env::httpProto() . ' ' . Response::NOT_FOUND_STATUS_CODE . ' Not Found';
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertNull($this->response->setStatusLine($newStatusLine));
        $this->assertSame($newStatusLine, $this->response->statusLine());
    }

    public function testHeadersAccessors() {
        $headers = $this->response->headers();
        $this->assertInstanceOf(ArrayObject::class, $headers);
        $headersToSet = [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="sample.pdf"',
        ];
        $headers->exchangeArray($headersToSet);
        $headers['Location'] = 'http://example.com';
        $this->assertSame(
            array_merge($headersToSet, ['Location' => 'http://example.com']),
            $this->response->headers()->getArrayCopy()
        );
    }

    public static function dataStatusCodeToStatusLine() {
        yield [
            200,
            'OK',
        ];
        yield [
            302,
            'Found',
        ];
        yield [
            304,
            'Not Modified',
        ];
        yield [
            400,
            'Bad Request',
        ];
        yield [
            403,
            'Forbidden',
        ];
        yield [
            404,
            'Not Found',
        ];
        yield [
            500,
            'Internal Server Error',
        ];
        yield [
            201,
            'Created',
        ];
        yield [
            144,
            'Unassigned',
        ];
    }

    /**
     * @dataProvider dataStatusCodeToStatusLine
     */
    public function testStatusCodeToStatusLine(int $statusCode, string $expectedReasonPhrase) {
        $this->response->setStatusCode($statusCode);
        $this->assertSame(
            Env::HTTP_PROTO . ' ' . $statusCode . ' ' . $expectedReasonPhrase,
            $this->response->statusLine()
        );
    }

    public function testSend() {
        $response = new class extends Response {
            public $called = [];

            protected function sendHeader(string $value): void {
                $this->called[] = [__FUNCTION__, func_get_args()];
            }
        };
        $body = 'Such page does not exist';
        $response->setStatusCode(404);
        $response->setBody($body);
        $response->headers()->exchangeArray(
            [
                'Location' => 'http://example.com',
            ]
        );
        ob_start();
        $response->send();
        $this->assertSame($body, ob_get_clean());
        $this->assertSame(
            [
                ['sendHeader', [Env::httpProto() . ' 404 Not Found']],
                ['sendHeader', ['Location: http://example.com']],
            ],
            $response->called
        );
    }

    public function testResetState() {
        $this->response->setStatusCode(404);
        $this->response->headers()['foo'] = 'bar';
        $this->response->setBody('test');
        $this->response->setStatusLine('Some status line');

        $this->response->resetState();

        $this->assertSame('', $this->response->body());
        $this->assertSame(Response::OK_STATUS_CODE, $this->response->statusCode());
        $this->assertSame([], $this->response->headers()->getArrayCopy());
        $this->assertSame($this->response->statusCodeToStatusLine(200), $this->response->statusLine());
    }
}
