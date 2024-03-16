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

use Exception;
use Morpho\App\Web\Negotiation\Exception\InvalidArgument;
use Morpho\App\Web\Negotiation\LanguageNegotiator;

class LanguageNegotiatorTest extends TestCase {

    /**
     * @var LanguageNegotiator
     */
    private $negotiator;

    protected function setUp(): void {
        $this->negotiator = new LanguageNegotiator();
    }

    /**
     * @dataProvider dataProviderForTestGetBest
     */
    public function testGetBest($accept, $priorities, $expected) {
        try {
            $accept = $this->negotiator->getBest($accept, $priorities);

            if (null === $accept) {
                $this->assertNull($expected);
            } else {
                $this->assertInstanceOf('Negotiation\AcceptLanguage', $accept);
                $this->assertEquals($expected, $accept->getValue());
            }
        } catch (Exception $e) {
            $this->assertEquals($expected, $e);
        }
    }

    public static function dataProviderForTestGetBest() {
        return [
            ['en, de', ['fr'], null],
            ['foo, bar, yo', ['baz', 'biz'], null],
            ['fr-FR, en;q=0.8', ['en-US', 'de-DE'], 'en-US'],
            ['en, *;q=0.9', ['fr'], 'fr'],
            ['foo, bar, yo', ['yo'], 'yo'],
            ['en; q=0.1, fr; q=0.4, bu; q=1.0', ['en', 'fr'], 'fr'],
            ['en; q=0.1, fr; q=0.4, fu; q=0.9, de; q=0.2', ['en', 'fu'], 'fu'],
            ['', ['en', 'fu'], new InvalidArgument('The header string should not be empty.')],
            ['fr, zh-Hans-CN;q=0.3', ['fr'], 'fr'],
            # Quality of source factors
            ['en;q=0.5,de', ['de;q=0.3', 'en;q=0.9'], 'en;q=0.9'],
            # Generic fallback
            ['fr-FR, en-US;q=0.8', ['fr'], 'fr'],
            ['fr-FR, en-US;q=0.8', ['fr', 'en-US'], 'fr'],
            ['fr-FR, en-US;q=0.8', ['fr-CA', 'en'], 'en'],
        ];
    }

    public function testGetBestRespectsQualityOfSource() {
        $accept = $this->negotiator->getBest('en;q=0.5,de', ['de;q=0.3', 'en;q=0.9']);
        $this->assertInstanceOf('Negotiation\AcceptLanguage', $accept);
        $this->assertEquals('en', $accept->getType());
    }

    /**
     * @dataProvider dataProviderForTestParseHeader
     */
    public function testParseHeader($header, $expected) {
        $accepts = $this->call_private_method('Negotiation\Negotiator', 'parseHeader', $this->negotiator, [$header]);

        $this->assertSame($expected, $accepts);
    }

    public static function dataProviderForTestParseHeader() {
        return [
            ['en; q=0.1, fr; q=0.4, bu; q=1.0', ['en; q=0.1', 'fr; q=0.4', 'bu; q=1.0']],
            ['en; q=0.1, fr; q=0.4, fu; q=0.9, de; q=0.2', ['en; q=0.1', 'fr; q=0.4', 'fu; q=0.9', 'de; q=0.2']],
        ];
    }

    /**
     * Given a accept header containing specific languages (here 'en-US', 'fr-FR')
     *  And priorities containing a generic version of that language
     * Then the best language is mapped to the generic one here 'fr'
     */
    public function testSpecificLanguageAreMappedToGeneric() {
        $acceptLanguageHeader = 'fr-FR, en-US;q=0.8';
        $priorities = ['fr'];

        $acceptHeader = $this->negotiator->getBest($acceptLanguageHeader, $priorities);

        $this->assertInstanceOf('Negotiation\AcceptHeader', $acceptHeader);
        $this->assertEquals('fr', $acceptHeader->getValue());
    }
}
