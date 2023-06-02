<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Morpho\Base\IFn;
use Negotiation\Accept;
use Negotiation\Exception\InvalidArgument;
use Negotiation\Negotiator;

use function strtolower;

class ContentNegotiator implements IFn {
    protected array $priorities = ['text/html', 'application/json'/*, 'application/xml;q=0.5'*/];

    protected string $defaultFormat = 'html';

    public function __invoke(mixed $request): string {
        $headers = $request->headers();
        if (!$headers->offsetExists('Accept')) {
            return $this->defaultFormat;
        }
        $acceptHeaderStr = $headers->offsetGet('Accept');

        // @TODO: Replace with own implementation for speed.
        // Perform Media Type Negotiation
        $negotiator = new Negotiator();
        try {
            /** @var Accept $mediaType */
            $mediaType = $negotiator->getBest($acceptHeaderStr, $this->priorities);
        } catch (InvalidArgument $e) {
            return $this->defaultFormat;
        }
        if (!$mediaType) {
            return $this->defaultFormat;
        }
        return strtolower($mediaType->getSubPart());
    }
}
