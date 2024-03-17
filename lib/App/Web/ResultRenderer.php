<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Morpho\Base\IFn;

class ResultRenderer implements IFn {
    protected array $priorities = ['text/html', 'application/json'/*, 'application/xml;q=0.5'*/];

    protected string $defaultFormat = ContentFormat::HTML;

    private $formatRendererFactory;

    public function __construct(callable $formatRendererFactory) {
        $this->formatRendererFactory = $formatRendererFactory;
    }

    public function __invoke(mixed $request): mixed {
        $response = $request->response;
        if (!$response->isRedirect()) {
            $allowedFormats = $response->formats;
            $currentFormat = $this->defaultFormat;
            /*
            $n = count($allowedFormats);
            if ($n == 1) {
                $currentFormat = $allowedFormats[0];
            } else {
                $headers = $request->headers();
                if (!$headers->offsetExists('Accept')) {
                    return $this->defaultFormat;
                }
                $acceptHeaderStr = $headers->offsetGet('Accept');

                $negotiator = new MediaTypeNegotiator();
                $mediaType = $negotiator->dataDetectBestMediaRange($acceptHeaderStr, $this->priorities);
                if (!$mediaType) {
                    $clientFormat = $this->defaultFormat;
                } else {
                    $clientFormat = strtolower($mediaType->subPart);
                }
                $key = array_search($clientFormat, $allowedFormats, true);
                if (false !== $key) {
                    $currentFormat = $allowedFormats[$key];
                }
            }
            */
            $renderer = ($this->formatRendererFactory)($currentFormat);
            $renderer->__invoke($request);
        }
        return $request;
    }
}
