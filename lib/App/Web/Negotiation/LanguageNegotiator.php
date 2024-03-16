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

class LanguageNegotiator extends AbstractNegotiator {
    /**
     * {@inheritdoc}
     */
    protected function acceptFactory($accept) {
        return new AcceptLanguage($accept);
    }

    /**
     * {@inheritdoc}
     */
    protected function match(AcceptHeader $acceptLanguage, AcceptHeader $priority, $index) {
        if (!$acceptLanguage instanceof AcceptLanguage || !$priority instanceof AcceptLanguage) {
            return null;
        }

        $ab = $acceptLanguage->getBasePart();
        $pb = $priority->getBasePart();

        $as = $acceptLanguage->getSubPart();
        $ps = $priority->getSubPart();

        $baseEqual = !strcasecmp((string)$ab, (string)$pb);
        $subEqual = !strcasecmp((string)$as, (string)$ps);

        if (($ab == '*' || $baseEqual) && ($as === null || $subEqual || null === $ps)) {
            $score = 10 * $baseEqual + $subEqual;

            return new AcceptMatch($acceptLanguage->getQuality() * $priority->getQuality(), $score, $index);
        }

        return null;
    }
}
