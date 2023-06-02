<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web\View;

use ArrayObject;
use Morpho\App\IRequest;
use Morpho\App\IResponse;
use Morpho\App\Web\View\JsonResponseRenderer;
use Morpho\Base\Ok;
use Morpho\Testing\TestCase;

class JsonResponseRendererTest extends TestCase {
    public function testCanRenderResult() {
        $result = new Ok(['foo' => 'bar']);

        $request = new class ($result) implements IRequest {
            public function __construct($result) {
                $this->response = new class ($result) extends ArrayObject implements IResponse {
                    private $body, $headers;

                    public function __construct($result) {
                        $this->headers = new ArrayObject();
                        parent::__construct(['result' => $result]);
                    }

                    public function setBody(string $body): void {
                        $this->body = $body;
                    }

                    public function headers() {
                        return $this->headers;
                    }

                    public function body(): string {
                        return $this->body;
                    }

                    public function isBodyEmpty(): bool {
                        // TODO: Implement isBodyEmpty() method.
                    }

                    public function send(): void {
                        // TODO: Implement send() method.
                    }

                    public function setStatusCode(int $statusCode): void {
                        // TODO: Implement setStatusCode() method.
                    }

                    public function statusCode(): int {
                        // TODO: Implement statusCode() method.
                    }

                    public function resetState(): void {
                        // TODO: Implement resetState() method.
                    }
                };
            }

            public function response(): IResponse {
                return $this->response;
            }

            public function getIterator(): \Traversable {
                // TODO: Implement getIterator() method.
            }

            public function offsetExists(mixed $offset): bool {
                // TODO: Implement offsetExists() method.
            }

            public function offsetGet(mixed $offset): mixed {
                // TODO: Implement offsetGet() method.
            }

            public function offsetSet(mixed $offset, mixed $value): void {
                // TODO: Implement offsetSet() method.
            }

            public function offsetUnset(mixed $offset): void {
                // TODO: Implement offsetUnset() method.
            }

            public function serialize() {
                // TODO: Implement serialize() method.
            }

            public function unserialize($serialized) {
                // TODO: Implement unserialize() method.
            }

            public function count(): int {
                // TODO: Implement count() method.
            }

            public function exchangeArray(object|array $arr) {
                // TODO: Implement exchangeArray() method.
            }

            public function isHandled(bool $flag = null): bool {
                // TODO: Implement isHandled() method.
            }

            public function setHandler(array $handler): void {
                // TODO: Implement setHandler() method.
            }

            public function handler(): array {
                // TODO: Implement handler() method.
            }

            public function setResponse(IResponse $response): void {
                // TODO: Implement setResponse() method.
            }

            public function args($namesOrIndexes = null): mixed {
                // TODO: Implement args() method.
            }
        };
        $jsonRenderer = new JsonResponseRenderer();

        $jsonRenderer($request);

        $response = $request->response();
        $rendered = $response->body();

        $this->assertJsonStringEqualsJsonString(json_encode($result), $rendered);
        $this->assertSame(['Content-Type' => 'application/json;charset=utf-8'], $response->headers()->getArrayCopy());
    }
}