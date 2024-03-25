<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use ArrayObject;
use Morpho\App\Web\HttpVersion;
use Morpho\App\Web\Response;
use Morpho\App\Web\StatusCode;
use Morpho\Test\Unit\App\MessageTest;
use Morpho\App\IMessage;

use PHPUnit\Framework\Attributes\DataProvider;

use function array_merge;
use function func_get_args;
use function ob_get_clean;
use function ob_start;

class ResponseTest extends MessageTest {
    private Response $response;

    protected function setUp(): void {
        parent::setUp();
        $this->response = new Response();
    }

    public function testHeadersAccessors() {
        $headers = $this->response->headers;
        $this->assertInstanceOf(ArrayObject::class, $headers);

        $newHeaders = [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="sample.pdf"',
        ];
        $headers->exchangeArray($newHeaders);
        $headers['Location'] = 'http://example.com';
        $this->assertSame(
            array_merge($newHeaders, ['Location' => 'http://example.com']),
            $this->response->headers->getArrayCopy()
        );
    }

    public function testSend() {
        $response = new class extends Response {
            public array $called = [];

            protected function sendHeader(string $header): void {
                $this->called[] = [__FUNCTION__, func_get_args()];
            }
        };
        $body = 'Such page does not exist';
        $response->body = $body;
        $response->statusLine->statusCode = StatusCode::NotFound;
        $locationHeaderValue = 'http://example.com';
        $response->headers->exchangeArray(['Location' => $locationHeaderValue]);

        ob_start();
        $response->send();
        $this->assertSame($body, ob_get_clean());

        $this->assertSame(
            [
                ['sendHeader', ['HTTP/1.1 404']],
                ['sendHeader', ['Location: ' . $locationHeaderValue]],
            ],
            $response->called
        );
    }

    public function testResetState() {
        $checkDefaultState = function () {
            $this->assertSame('HTTP/1.1 200', (string)$this->response->statusLine);
            $this->assertSame([], $this->response->headers->getArrayCopy());
            $this->assertSame('', $this->response->body);
        };

        $checkDefaultState();

        $this->response->statusLine->statusCode = StatusCode::NotFound;
        $this->response->headers['foo'] = 'bar';
        $this->response->body = 'test';
        $this->response->statusLine->httpVersion = HttpVersion::V3;

        $this->response->resetState();

        $checkDefaultState();
    }

    public function testRedirect() {
        $this->assertFalse($this->response->isRedirect());

        $redirect = $this->response->mkRedirect('/foo/bar');
        $this->assertNotSame($this->response, $redirect);

        $this->assertFalse($this->response->isRedirect());
        $this->assertSame('HTTP/1.1 200', $this->response->statusLine->__toString());
        $this->assertTrue($redirect->isRedirect());
        $this->assertSame('HTTP/1.1 302', $redirect->statusLine->__toString());
    }

    protected function mkMessage(): IMessage {
        return clone $this->response;
    }
}
