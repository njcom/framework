<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web\View;

use ArrayObject;
use Morpho\App\IResponse;
use Morpho\App\ModuleIndex;
use Morpho\App\Web\View\HtmlResponseRenderer;
use Morpho\App\Web\View\PhpTemplateEngine;
use Morpho\Base\IFn;
use Morpho\Testing\TestCase;

class HtmlResponseRendererTest extends TestCase {
    public function testInterface() {
        $this->assertInstanceOf(IFn::class, new HtmlResponseRenderer($this->createMock(PhpTemplateEngine::class), $this->createMock(ModuleIndex::class), ''));
    }

    public function testInvoke() {
        $response = new class extends ArrayObject implements IResponse {
            public string $body;
            public ArrayObject $headers;

            public function __construct() {
                parent::__construct();
                $this->headers = new ArrayObject();
            }

            public function send(): mixed {
                return null;
            }
        };

        $request = new class {
            public mixed $response;
        };
        $request->response = $response;
        $htmlSample = 'This is a <main>This is a body text.</main> page text.';
        $renderer = new class ($this->createMock(PhpTemplateEngine::class), $this->createMock(ModuleIndex::class), 'foo/bar', $htmlSample) extends HtmlResponseRenderer {
            private string $htmlSample;

            public function __construct(PhpTemplateEngine $templateEngine, ModuleIndex $moduleIndex, string $pageRenderingModule, string $htmlSample) {
                parent::__construct($templateEngine, $moduleIndex, $pageRenderingModule);
                $this->htmlSample = $htmlSample;
            }

            protected function renderHtml($request): string {
                return $this->htmlSample;
            }
        };

        $renderer->__invoke($request);

        $this->assertSame($htmlSample, $response->body);
        $this->assertSame(['Content-Type' => 'text/html;charset=utf-8'], $response->headers->getArrayCopy());
    }
}
