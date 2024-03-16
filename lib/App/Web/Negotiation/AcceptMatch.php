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

final class AcceptMatch {
    /**
     * @var float
     */
    public $quality;

    /**
     * @var int
     */
    public $score;

    /**
     * @var int
     */
    public $index;

    public function __construct($quality, $score, $index) {
        $this->quality = $quality;
        $this->score = $score;
        $this->index = $index;
    }

    /**
     * @param AcceptMatch $a
     * @param AcceptMatch $b
     *
     * @return int
     */
    public static function compare(AcceptMatch $a, AcceptMatch $b) {
        if ($a->quality !== $b->quality) {
            return $a->quality > $b->quality ? -1 : 1;
        }

        if ($a->index !== $b->index) {
            return $a->index > $b->index ? 1 : -1;
        }

        return 0;
    }

    /**
     * @param array       $carry reduced array
     * @param AcceptMatch $match match to be reduced
     *
     * @return AcceptMatch[]
     */
    public static function reduce(array $carry, AcceptMatch $match) {
        if (!isset($carry[$match->index]) || $carry[$match->index]->score < $match->score) {
            $carry[$match->index] = $match;
        }

        return $carry;
    }
}
