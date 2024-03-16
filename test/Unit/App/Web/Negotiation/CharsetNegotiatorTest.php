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

use Morpho\App\Web\Negotiation\AcceptCharset;
use Morpho\App\Web\Negotiation\CharsetNegotiator;
use Morpho\App\Web\Negotiation\Exception\InvalidArgument;

class CharsetNegotiatorTest extends TestCase {

    /**
     * @var CharsetNegotiator
     */
    private $negotiator;

    protected function setUp(): void {
        $this->negotiator = new CharsetNegotiator();
    }

    public function testGetBestReturnsNullWithUnmatchedHeader() {
        $this->assertNull($this->negotiator->getBest('foo, bar, yo', ['baz']));
    }

    /**
     * 'bu' has the highest quality rating, but is non-existent,
     * so we expect the next highest rated 'fr' content to be returned.
     *
     * See: http://svn.apache.org/repos/asf/httpd/test/framework/trunk/t/modules/negotiation.t
     */
    public function testGetBestIgnoresNonExistentContent() {
        $acceptCharset = 'en; q=0.1, fr; q=0.4, bu; q=1.0';
        $accept = $this->negotiator->getBest($acceptCharset, ['en', 'fr']);

        $this->assertInstanceOf(AcceptCharset::class, $accept);
        $this->assertEquals('fr', $accept->getValue());
    }

    /**
     * @dataProvider dataProviderForTestGetBest
     */
    public function testGetBest($accept, $priorities, $expected) {
        if (is_null($expected)) {
            $this->expectException(InvalidArgument::class);
        }

        $accept = $this->negotiator->getBest($accept, $priorities);
        if (null === $accept) {
            $this->assertNull($expected);
        } else {
            $this->assertInstanceOf(AcceptCharset::class, $accept);
            $this->assertSame($expected, $accept->getValue());
        }
    }

    public static function dataProviderForTestGetBest() {
        $pearCharset = 'ISO-8859-1, Big5;q=0.6,utf-8;q=0.7, *;q=0.5';
        $pearCharset2 = 'ISO-8859-1, Big5;q=0.6,utf-8;q=0.7';

        return [
            [$pearCharset, ['utf-8', 'big5', 'iso-8859-1', 'shift-jis',], 'iso-8859-1'],
            [$pearCharset, ['utf-8', 'big5', 'shift-jis',], 'utf-8'],
            [$pearCharset, ['Big5', 'shift-jis',], 'Big5'],
            [$pearCharset, ['shift-jis',], 'shift-jis'],
            [$pearCharset2, ['utf-8', 'big5', 'iso-8859-1', 'shift-jis',], 'iso-8859-1'],
            [$pearCharset2, ['utf-8', 'big5', 'shift-jis',], 'utf-8'],
            [$pearCharset2, ['Big5', 'shift-jis',], 'Big5'],
            ['utf-8;q=0.6,iso-8859-5;q=0.9', ['iso-8859-5', 'utf-8',], 'iso-8859-5'],
            ['', ['iso-8859-5', 'utf-8',], null],
            ['en, *;q=0.9', ['fr'], 'fr'],
            # Quality of source factors
            [$pearCharset, ['iso-8859-1;q=0.5', 'utf-8', 'utf-16;q=1.0'], 'utf-8'],
            [$pearCharset, ['iso-8859-1;q=0.8', 'utf-8', 'utf-16;q=1.0'], 'iso-8859-1;q=0.8'],
        ];
    }

    public function testGetBestRespectsPriorities() {
        $accept = $this->negotiator->getBest('foo, bar, yo', ['yo']);

        $this->assertInstanceOf(AcceptCharset::class, $accept);
        $this->assertEquals('yo', $accept->getValue());
    }

    public function testGetBestDoesNotMatchPriorities() {
        $acceptCharset = 'en, de';
        $priorities = ['fr'];

        $this->assertNull($this->negotiator->getBest($acceptCharset, $priorities));
    }

    public function testGetBestRespectsQualityOfSource() {
        $accept = $this->negotiator->getBest('utf-8;q=0.5,iso-8859-1', ['iso-8859-1;q=0.3', 'utf-8;q=0.9', 'utf-16;q=1.0']);
        $this->assertInstanceOf(AcceptCharset::class, $accept);
        $this->assertEquals('utf-8', $accept->getType());
    }

    /**
     * @dataProvider dataProviderForTestParseHeader
     */
    public function testParseHeader($header, $expected) {
        $accepts = $this->call_private_method(CharsetNegotiator::class, 'parseHeader', $this->negotiator, [$header]);

        $this->assertSame($expected, $accepts);
    }

    public static function dataProviderForTestParseHeader() {
        return [
            ['*;q=0.3,ISO-8859-1,utf-8;q=0.7', ['*;q=0.3', 'ISO-8859-1', 'utf-8;q=0.7']],
            ['*;q=0.3,ISO-8859-1;q=0.7,utf-8;q=0.7', ['*;q=0.3', 'ISO-8859-1;q=0.7', 'utf-8;q=0.7']],
            ['*;q=0.3,utf-8;q=0.7,ISO-8859-1;q=0.7', ['*;q=0.3', 'utf-8;q=0.7', 'ISO-8859-1;q=0.7']],
            ['iso-8859-5, unicode-1-1;q=0.8', ['iso-8859-5', 'unicode-1-1;q=0.8']],
        ];
    }
}
