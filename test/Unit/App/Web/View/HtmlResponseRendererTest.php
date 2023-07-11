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
use Morpho\App\ModuleIndex;
use Morpho\App\Web\View\HtmlResponseRenderer;
use Morpho\Testing\TestCase;

class HtmlResponseRendererTest extends TestCase {
    public function testInterface() {
        $this->assertIsCallable(new HtmlResponseRenderer(null, $this->createMock(ModuleIndex::class), ''));
    }

    public function testInvoke() {
        $response = new class extends ArrayObject implements IResponse {
            private string $body;
            private ArrayObject $headers;

            public function __construct() {
                parent::__construct();
                $this->headers = new ArrayObject();
            }

            public function headers() {
                return $this->headers;
            }

            public function body(): string {
                return $this->body;
            }

            public function setBody(string $body): void {
                $this->body = $body;
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
        $request = $this->createConfiguredMock(IRequest::class, ['response' => $response]);
        $htmlSample = 'This is a <main>This is a body text.</main> page text.';
        $renderer = new class (new class {
        }, $this->createMock(ModuleIndex::class), 'foo/bar', $htmlSample) extends HtmlResponseRenderer {
            private string $htmlSample;

            public function __construct($templateEngine, $moduleIndex, string $pageRenderingModule, string $htmlSample) {
                parent::__construct($templateEngine, $moduleIndex, $pageRenderingModule);
                $this->htmlSample = $htmlSample;
            }

            protected function renderHtml($request): string {
                return $this->htmlSample;
            }
        };

        $renderer->__invoke($request);

        $this->assertSame($htmlSample, $response->body());
        $this->assertSame(['Content-Type' => 'text/html;charset=utf-8'], $response->headers()->getArrayCopy());
    }
}
