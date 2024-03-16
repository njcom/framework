<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
/**
 * Based on https://github.com/willdurand/Negotiation library, original author William Durand, MIT license.
 * See [RFC 7231](https://tools.ietf.org/html/rfc7231#section-5.3)
 */

namespace Morpho\App\Web\Negotiation;

final class AcceptLanguage extends BaseAccept implements AcceptHeader {
    private $language;
    private $script;
    private $region;

    public function __construct($value) {
        parent::__construct($value);

        $parts = explode('-', $this->type);

        if (2 === count($parts)) {
            $this->language = $parts[0];
            $this->region = $parts[1];
        } elseif (1 === count($parts)) {
            $this->language = $parts[0];
        } elseif (3 === count($parts)) {
            $this->language = $parts[0];
            $this->script = $parts[1];
            $this->region = $parts[2];
        } else {
            // TODO: this part is never reached...
            throw new Exception\InvalidLanguage();
        }
    }

    /**
     * @return string
     */
    public function getSubPart() {
        return $this->region;
    }

    /**
     * @return string
     */
    public function getBasePart() {
        return $this->language;
    }
}
