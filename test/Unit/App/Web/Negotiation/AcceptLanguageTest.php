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

use Morpho\App\Web\Negotiation\AcceptLanguage;

class AcceptLanguageTest extends TestCase {

    /**
     * @dataProvider dataProviderForGetType
     */
    public function testGetType($header, $expected) {
        $accept = new AcceptLanguage($header);
        $actual = $accept->getType();
        $this->assertEquals($expected, $actual);
    }

    public static function dataProviderForGetType() {
        return [
            ['en;q=0.7', 'en'],
            ['en-GB;q=0.8', 'en-gb'],
            ['da', 'da'],
            ['en-gb;q=0.8', 'en-gb'],
            ['es;q=0.7', 'es'],
            ['fr ; q= 0.1', 'fr'],
            ['', null],
            [null, null],
        ];
    }

    /**
     * @dataProvider dataProviderForGetValue
     */
    public function testGetValue($header, $expected) {
        $accept = new AcceptLanguage($header);
        $actual = $accept->getValue();
        $this->assertEquals($expected, $actual);
    }

    public static function dataProviderForGetValue() {
        return [
            ['en;q=0.7', 'en;q=0.7'],
            ['en-GB;q=0.8', 'en-GB;q=0.8'],
        ];
    }
}
