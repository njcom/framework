<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web\View;

use Morpho\App\Web\View\JsonResponseRenderer;
use Morpho\Testing\TestCase;

class JsonResponseRendererTest extends TestCase {
    public function testCanRenderResult() {
        $jsonRenderer = new JsonResponseRenderer();

        $result = ['foo' => 'bar'];
        $response = new class extends \ArrayObject {
            public string $body;
            public \ArrayObject $headers;

            public function __construct() {
                parent::__construct();
                $this->headers = new \ArrayObject();
            }
        };
        $request = new class {
            public mixed $response;
        };
        $response['result'] = $result;
        $request->response = $response;

        $request = $jsonRenderer($request);

        $response = $request->response;

        $this->assertJsonStringEqualsJsonString(json_encode($result), $response->body);
        $this->assertSame(['Content-Type' => 'application/json;charset=utf-8'], $response->headers->getArrayCopy());
    }
}
