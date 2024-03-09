<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Morpho\Base\IFn;

class ResultRenderer implements IFn {
    private ContentNegotiator $contentNegotiator;

    private $rendererFactory;

    public function __construct(callable $rendererFactory) {
        $this->rendererFactory = $rendererFactory;
        // @todo: pass as argument
        $this->contentNegotiator = new ContentNegotiator();
    }

    public function __invoke(mixed $request): mixed {
        $response = $request->response;
        if (!$response->isRedirect()) {
            $formats = $response->formats();
            if (count($formats)) {
                $currentFormat = null;
                if (count($formats) > 1) {
                    $contentNegotiator = $this->contentNegotiator;
                    $clientFormat = $contentNegotiator->__invoke($request);
                    $key = array_search($clientFormat, $formats, true);
                    if (false !== $key) {
                        $currentFormat = $formats[$key];
                    }
                } else {
                    $currentFormat = current($formats);
                }
                if ($currentFormat) {
                    $renderer = ($this->rendererFactory)($currentFormat);
                    $renderer->__invoke($request);
                }
            }
        }
        return $request;
    }
}
