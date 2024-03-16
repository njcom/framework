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

final class Accept extends BaseAccept implements AcceptHeader {
    private $basePart;

    private $subPart;

    public function __construct($value) {
        parent::__construct($value);

        if ($this->type === '*') {
            $this->type = '*/*';
        }

        $parts = explode('/', $this->type);

        if (count($parts) !== 2 || !$parts[0] || !$parts[1]) {
            throw new Exception\InvalidMediaType();
        }

        $this->basePart = $parts[0];
        $this->subPart = $parts[1];
    }

    /**
     * @return string
     */
    public function getSubPart() {
        return $this->subPart;
    }

    /**
     * @return string
     */
    public function getBasePart() {
        return $this->basePart;
    }
}
