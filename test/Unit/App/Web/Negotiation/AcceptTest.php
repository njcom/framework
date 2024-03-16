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

use Morpho\App\Web\Negotiation\Accept;

class AcceptTest extends TestCase {
    public function testGetParameter() {
        $accept = new Accept('foo/bar; q=1; hello=world');

        $this->assertTrue($accept->hasParameter('hello'));
        $this->assertEquals('world', $accept->getParameter('hello'));
        $this->assertFalse($accept->hasParameter('unknown'));
        $this->assertNull($accept->getParameter('unknown'));
        $this->assertFalse($accept->getParameter('unknown', false));
        $this->assertSame('world', $accept->getParameter('hello', 'goodbye'));
    }

    /**
     * @dataProvider dataProviderForTestGetNormalizedValue
     */
    public function testGetNormalizedValue($header, $expected) {
        $accept = new Accept($header);
        $actual = $accept->getNormalizedValue();
        $this->assertEquals($expected, $actual);
    }

    public static function dataProviderForTestGetNormalizedValue() {
        return [
            ['text/html; z=y; a=b; c=d', 'text/html; a=b; c=d; z=y'],
            ['application/pdf; q=1; param=p', 'application/pdf; param=p'],
        ];
    }

    /**
     * @dataProvider dataProviderForGetType
     */
    public function testGetType($header, $expected) {
        $accept = new Accept($header);
        $actual = $accept->getType();
        $this->assertEquals($expected, $actual);
    }

    public static function dataProviderForGetType() {
        return [
            ['text/html;hello=world', 'text/html'],
            ['application/pdf', 'application/pdf'],
            ['application/xhtml+xml;q=0.9', 'application/xhtml+xml'],
            ['text/plain; q=0.5', 'text/plain'],
            ['text/html;level=2;q=0.4', 'text/html'],
            ['text/html ; level = 2   ; q = 0.4', 'text/html'],
            ['text/*', 'text/*'],
            ['text/* ;q=1 ;level=2', 'text/*'],
            ['*/*', '*/*'],
            ['*', '*/*'],
            ['*/* ; param=555', '*/*'],
            ['* ; param=555', '*/*'],
            ['TEXT/hTmL;leVel=2; Q=0.4', 'text/html'],
        ];
    }

    /**
     * @dataProvider dataProviderForGetValue
     */
    public function testGetValue($header, $expected) {
        $accept = new Accept($header);
        $actual = $accept->getValue();
        $this->assertEquals($expected, $actual);
    }

    public static function dataProviderForGetValue() {
        return [
            ['text/html;hello=world  ;q=0.5', 'text/html;hello=world  ;q=0.5'],
            ['application/pdf', 'application/pdf'],
        ];
    }
}
