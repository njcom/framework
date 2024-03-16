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

use Morpho\App\Web\Negotiation\EncodingNegotiator;

class EncodingNegotiatorTest extends TestCase {

    /**
     * @var EncodingNegotiator
     */
    private $negotiator;

    protected function setUp(): void {
        $this->negotiator = new EncodingNegotiator();
    }

    public function testGetBestReturnsNullWithUnmatchedHeader() {
        $this->assertNull($this->negotiator->getBest('foo, bar, yo', ['baz']));
    }

    /**
     * @dataProvider dataProviderForTestGetBest
     */
    public function testGetBest($accept, $priorities, $expected) {
        $accept = $this->negotiator->getBest($accept, $priorities);

        if (null === $accept) {
            $this->assertNull($expected);
        } else {
            $this->assertInstanceOf('Negotiation\AcceptEncoding', $accept);
            $this->assertEquals($expected, $accept->getValue());
        }
    }

    public static function dataProviderForTestGetBest() {
        return [
            ['gzip;q=1.0, identity; q=0.5, *;q=0', ['identity'], 'identity'],
            ['gzip;q=0.5, identity; q=0.5, *;q=0.7', ['bzip', 'foo'], 'bzip'],
            ['gzip;q=0.7, identity; q=0.5, *;q=0.7', ['gzip', 'foo'], 'gzip'],
            # Quality of source factors
            ['gzip;q=0.7,identity', ['identity;q=0.5', 'gzip;q=0.9'], 'gzip;q=0.9'],
        ];
    }

    public function testGetBestRespectsQualityOfSource() {
        $accept = $this->negotiator->getBest('gzip;q=0.7,identity', ['identity;q=0.5', 'gzip;q=0.9']);
        $this->assertInstanceOf('Negotiation\AcceptEncoding', $accept);
        $this->assertEquals('gzip', $accept->getType());
    }

    /**
     * @dataProvider dataProviderForTestParseAcceptHeader
     */
    public function testParseAcceptHeader($header, $expected) {
        $accepts = $this->call_private_method('Negotiation\Negotiator', 'parseHeader', $this->negotiator, [$header]);

        $this->assertSame($expected, $accepts);
    }

    public static function dataProviderForTestParseAcceptHeader() {
        return [
            ['gzip,deflate,sdch', ['gzip', 'deflate', 'sdch']],
            ["gzip, deflate\t,sdch", ['gzip', 'deflate', 'sdch']],
            ['gzip;q=1.0, identity; q=0.5, *;q=0', ['gzip;q=1.0', 'identity; q=0.5', '*;q=0']],
        ];
    }
}
