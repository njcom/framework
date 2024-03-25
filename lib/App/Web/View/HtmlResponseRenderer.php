<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use Morpho\App\ModuleIndex;
use Morpho\Base\IFn;

use function Morpho\Base\dasherize;

class HtmlResponseRenderer implements IFn {
    private PhpTemplateEngine $templateEngine;

    private ModuleIndex $moduleIndex;

    private string $pageRenderingModule;

    public function __construct(PhpTemplateEngine $templateEngine, ModuleIndex $moduleIndex, string $pageRenderingModule) {
        $this->templateEngine = $templateEngine;
        $this->moduleIndex = $moduleIndex;
        $this->pageRenderingModule = $pageRenderingModule;
    }

    public function __invoke(mixed $context): mixed {
        $response = $context->response;
        $html = $this->renderHtml($context);
        $response->body = $html;
        // https://tools.ietf.org/html/rfc7231#section-3.1.1
        $response->headers['Content-Type'] = 'text/html;charset=utf-8';
        return $context;
    }

    protected function renderHtml($request): string {
        $response = $request->response;
        $handler = $request->handler;
        $actionResult = $response['result'];

        /** @var \Morpho\App\BackendModule $handlerModule */
        $handlerModule = $this->moduleIndex->module($handler['module']);

        $this->templateEngine->setBaseTargetDirPath($handlerModule->compiledTemplatesDirPath());

        $this->templateEngine
            ->addBaseSourceDirPath($handlerModule->viewDirPath())
            ->addBaseSourceDirPath($this->moduleIndex->module($this->pageRenderingModule)->viewDirPath());

        if (!isset($actionResult['_view'])) {
            $actionResult['_view'] = dasherize($handler['method']);
        }
        if (!str_contains($actionResult['_view'], '/')) {
            $actionResult['_view'] = $handler['controllerPath'] . '/' . $actionResult['_view'];
        }

        // Save view in request to access it during page rendering.
        $request['view'] = $actionResult['_view'];

        /*
        $actionResult['_handler'] = $handler;
        $actionResult['_module'] = $handler['module'];
        */

        $html = $this->templateEngine->__invoke($actionResult);

        //if (!$response->allowAjax || !$request->isAjax()) {
            $page = $actionResult['_parentView'] ?? ['_view' => 'index'];
            $page['body'] = $html;
            $html = $this->templateEngine->__invoke($page);
        //}

        return $html;
    }
}
