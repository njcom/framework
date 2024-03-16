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

namespace Morpho\Test\Unit\App\Web\Negotiation;

use Morpho\App\Web\Negotiation\AcceptMatch;

class MatchTest extends TestCase {
    /**
     * @dataProvider dataProviderForTestCompare
     */
    public function testCompare($match1, $match2, $expected) {
        $this->assertEquals($expected, AcceptMatch::compare($match1, $match2));
    }

    public static function dataProviderForTestCompare() {
        return [
            [new AcceptMatch(1.0, 110, 1), new AcceptMatch(1.0, 111, 1), 0],
            [new AcceptMatch(0.1, 10, 1), new AcceptMatch(0.1, 10, 2), -1],
            [new AcceptMatch(0.5, 110, 5), new AcceptMatch(0.5, 11, 4), 1],
            [new AcceptMatch(0.4, 110, 1), new AcceptMatch(0.6, 111, 3), 1],
            [new AcceptMatch(0.6, 110, 1), new AcceptMatch(0.4, 111, 3), -1],
        ];
    }

    /**
     * @dataProvider dataProviderForTestReduce
     */
    public function testReduce($carry, $match, $expected) {
        $this->assertEquals($expected, AcceptMatch::reduce($carry, $match));
    }

    public static function dataProviderForTestReduce() {
        return [
            [
                [1 => new AcceptMatch(1.0, 10, 1)],
                new AcceptMatch(0.5, 111, 1),
                [1 => new AcceptMatch(0.5, 111, 1)],
            ],
            [
                [1 => new AcceptMatch(1.0, 110, 1)],
                new AcceptMatch(0.5, 11, 1),
                [1 => new AcceptMatch(1.0, 110, 1)],
            ],
            [
                [0 => new AcceptMatch(1.0, 10, 1)],
                new AcceptMatch(0.5, 111, 1),
                [0 => new AcceptMatch(1.0, 10, 1), 1 => new AcceptMatch(0.5, 111, 1)],
            ],
        ];
    }
}
