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

use Morpho\App\Web\Negotiation\BaseAccept;

class BaseAcceptTest extends TestCase {
    /**
     * @dataProvider dataProviderForParseParameters
     */
    public function testParseParameters($value, $expected) {
        $accept = new DummyAccept($value);
        $parameters = $accept->getParameters();

        // TODO: hack-ish... this is needed because logic in BaseAccept
        //constructor drops the quality from the parameter set.
        if (false !== strpos($value, 'q')) {
            $parameters['q'] = $accept->getQuality();
        }

        $this->assertCount(count($expected), $parameters);

        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $parameters);
            $this->assertEquals($value, $parameters[$key]);
        }
    }

    public static function dataProviderForParseParameters() {
        return [
            [
                'application/json ;q=1.0; level=2;foo= bar',
                [
                    'q'     => 1.0,
                    'level' => 2,
                    'foo'   => 'bar',
                ],
            ],
            [
                'application/json ;q = 1.0; level = 2;     FOO  = bAr',
                [
                    'q'     => 1.0,
                    'level' => 2,
                    'foo'   => 'bAr',
                ],
            ],
            [
                'application/json;q=1.0',
                [
                    'q' => 1.0,
                ],
            ],
            [
                'application/json;foo',
                [],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderBuildParametersString
     */

    public function testBuildParametersString($value, $expected) {
        $accept = new DummyAccept($value);

        $this->assertEquals($expected, $accept->getNormalizedValue());
    }

    public static function dataProviderBuildParametersString() {
        return [
            ['media/type; xxx = 1.0;level=2;foo=bar', 'media/type; foo=bar; level=2; xxx=1.0'],
        ];
    }
}

class DummyAccept extends BaseAccept {
}
