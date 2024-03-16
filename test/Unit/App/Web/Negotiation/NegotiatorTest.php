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
use Morpho\App\Web\Negotiation\Accept;
use Morpho\App\Web\Negotiation\AcceptMatch;
use Morpho\App\Web\Negotiation\Exception\InvalidArgument;
use Morpho\App\Web\Negotiation\Exception\InvalidMediaType;
use Morpho\App\Web\Negotiation\Negotiator;

class NegotiatorTest extends TestCase {

    /**
     * @var Negotiator
     */
    private $negotiator;

    protected function setUp(): void {
        $this->negotiator = new Negotiator();
    }

    /**
     * @dataProvider dataProviderForTestGetBest
     */
    public function testGetBest($header, $priorities, $expected) {
        try {
            $acceptHeader = $this->negotiator->getBest($header, $priorities);
        } catch (Exception $e) {
            $this->assertEquals($expected, $e);

            return;
        }

        if ($acceptHeader === null) {
            $this->assertNull($expected);

            return;
        }

        $this->assertInstanceOf('Negotiation\Accept', $acceptHeader);

        $this->assertSame($expected[0], $acceptHeader->getType());
        $this->assertSame($expected[1], $acceptHeader->getParameters());
    }

    public static function dataProviderForTestGetBest() {
        $pearAcceptHeader = 'text/html,application/xhtml+xml,application/xml;q=0.9,text/*;q=0.7,*/*,image/gif; q=0.8, image/jpeg; q=0.6, image/*';
        $rfcHeader = 'text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5';

        return [
            # exceptions
            ['/qwer', ['f/g'], null],
            ['/qwer,f/g', ['f/g'], ['f/g', []]],
            ['foo/bar', ['/qwer'], new InvalidMediaType()],
            ['', ['foo/bar'], new InvalidArgument('The header string should not be empty.')],
            ['*/*', [], new InvalidArgument('A set of server priorities should be given.')],

            # See: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
            [$rfcHeader, ['text/html;level=1'], ['text/html', ['level' => '1']]],
            [$rfcHeader, ['text/html'], ['text/html', []]],
            [$rfcHeader, ['text/plain'], ['text/plain', []]],
            [$rfcHeader, ['image/jpeg',], ['image/jpeg', []]],
            [$rfcHeader, ['text/html;level=2'], ['text/html', ['level' => '2']]],
            [$rfcHeader, ['text/html;level=3'], ['text/html', ['level' => '3']]],

            ['text/*;q=0.7, text/html;q=0.3, */*;q=0.5, image/png;q=0.4', ['text/html', 'image/png'], ['image/png', []]],
            ['image/png;q=0.1, text/plain, audio/ogg;q=0.9', ['image/png', 'text/plain', 'audio/ogg'], ['text/plain', []]],
            ['image/png, text/plain, audio/ogg', ['baz/asdf'], null],
            ['image/png, text/plain, audio/ogg', ['audio/ogg'], ['audio/ogg', []]],
            ['image/png, text/plain, audio/ogg', ['YO/SuP'], null],
            ['text/html; charset=UTF-8, application/pdf', ['text/html; charset=UTF-8'], ['text/html', ['charset' => 'UTF-8']]],
            ['text/html; charset=UTF-8, application/pdf', ['text/html'], null],
            ['text/html, application/pdf', ['text/html; charset=UTF-8'], ['text/html', ['charset' => 'UTF-8']]],
            # PEAR HTTP2 tests - have been altered from original!
            [$pearAcceptHeader, ['image/gif', 'image/png', 'application/xhtml+xml', 'application/xml', 'text/html', 'image/jpeg', 'text/plain',], ['image/png', []]],
            [$pearAcceptHeader, ['image/gif', 'application/xhtml+xml', 'application/xml', 'image/jpeg', 'text/plain',], ['application/xhtml+xml', []]],
            [$pearAcceptHeader, ['image/gif', 'application/xml', 'image/jpeg', 'text/plain',], ['application/xml', []]],
            [$pearAcceptHeader, ['image/gif', 'image/jpeg', 'text/plain'], ['image/gif', []]],
            [$pearAcceptHeader, ['text/plain', 'image/png', 'image/jpeg'], ['image/png', []]],
            [$pearAcceptHeader, ['image/jpeg', 'image/gif',], ['image/gif', []]],
            [$pearAcceptHeader, ['image/png',], ['image/png', []]],
            [$pearAcceptHeader, ['audio/midi',], ['audio/midi', []]],
            ['text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', ['application/rss+xml'], ['application/rss+xml', []]],
            # LWS / case sensitivity
            ['text/* ; q=0.3, TEXT/html ;Q=0.7, text/html ; level=1, texT/Html ;leVel = 2 ;q=0.4, */* ; q=0.5', ['text/html; level=2'], ['text/html', ['level' => '2']]],
            ['text/* ; q=0.3, text/html;Q=0.7, text/html ;level=1, text/html; level=2;q=0.4, */*;q=0.5', ['text/HTML; level=3'], ['text/html', ['level' => '3']]],
            # Incompatible
            ['text/html', ['application/rss'], null],
            # IE8 Accept header
            ['image/jpeg, application/x-ms-application, image/gif, application/xaml+xml, image/pjpeg, application/x-ms-xbap, */*', ['text/html', 'application/xhtml+xml'], ['text/html', []]],
            # Quality of source factors
            [$rfcHeader, ['text/html;q=0.4', 'text/plain'], ['text/plain', []]],
            # Wildcard "plus" parts (e.g., application/vnd.api+json)
            ['application/vnd.api+json', ['application/json', 'application/*+json'], ['application/*+json', []]],
            ['application/json;q=0.7, application/*+json;q=0.7', ['application/hal+json', 'application/problem+json'], ['application/hal+json', []]],
            ['application/json;q=0.7, application/problem+*;q=0.7', ['application/hal+xml', 'application/problem+xml'], ['application/problem+xml', []]],
            [$pearAcceptHeader, ['application/*+xml'], ['application/*+xml', []]],
            # @see https://github.com/willdurand/Negotiation/issues/93
            ['application/hal+json', ['application/ld+json', 'application/hal+json', 'application/xml', 'text/xml', 'application/json', 'text/html'], ['application/hal+json', []]],
        ];
    }

    /**
     * @dataProvider dataProviderForTestGetOrderedElements
     */
    public function testGetOrderedElements($header, $expected) {
        try {
            $elements = $this->negotiator->getOrderedElements($header);
        } catch (Exception $e) {
            $this->assertEquals($expected, $e);

            return;
        }

        if (empty($elements)) {
            $this->assertNull($expected);

            return;
        }

        $this->assertInstanceOf('Negotiation\Accept', $elements[0]);

        foreach ($expected as $key => $item) {
            $this->assertSame($item, $elements[$key]->getValue());
        }
    }

    public static function dataProviderForTestGetOrderedElements() {
        return [
            // error cases
            ['', new InvalidArgument('The header string should not be empty.')],
            ['/qwer', null],

            // first one wins as no quality modifiers
            ['text/html, text/xml', ['text/html', 'text/xml']],

            // ordered by quality modifier
            [
                'text/html;q=0.3, text/html;q=0.7',
                ['text/html;q=0.7', 'text/html;q=0.3'],
            ],
            // ordered by quality modifier - the one with no modifier wins, level not taken into account
            [
                'text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5',
                ['text/html;level=1', 'text/html;q=0.7', '*/*;q=0.5', 'text/html;level=2;q=0.4', 'text/*;q=0.3'],
            ],
        ];
    }

    public function testGetBestRespectsQualityOfSource() {
        $accept = $this->negotiator->getBest('text/html,text/*;q=0.7', ['text/html;q=0.5', 'text/plain;q=0.9']);
        $this->assertInstanceOf('Negotiation\Accept', $accept);
        $this->assertEquals('text/plain', $accept->getType());
    }

    public function testGetBestInvalidMediaType() {
        $this->expectException(\Negotiation\Exception\InvalidMediaType::class);
        $header = 'sdlfkj20ff; wdf';
        $priorities = ['foo/qwer'];

        $this->negotiator->getBest($header, $priorities, true);
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
            ['text/html ;   q=0.9', ['text/html ;   q=0.9']],
            ['text/html,application/xhtml+xml', ['text/html', 'application/xhtml+xml']],
            [',,text/html;q=0.8 , , ', ['text/html;q=0.8']],
            ['text/html;charset=utf-8; q=0.8', ['text/html;charset=utf-8; q=0.8']],
            ['text/html; foo="bar"; q=0.8 ', ['text/html; foo="bar"; q=0.8']],
            ['text/html; foo="bar"; qwer="asdf", image/png', ['text/html; foo="bar"; qwer="asdf"', "image/png"]],
            ['text/html ; quoted_comma="a,b  ,c,",application/xml;q=0.9,*/*;charset=utf-8; q=0.8', ['text/html ; quoted_comma="a,b  ,c,"', 'application/xml;q=0.9', '*/*;charset=utf-8; q=0.8']],
            ['text/html, application/json;q=0.8, text/csv;q=0.7', ['text/html', 'application/json;q=0.8', 'text/csv;q=0.7']],
        ];
    }

    /**
     * @dataProvider dataProviderForTestFindMatches
     */
    public function testFindMatches($headerParts, $priorities, $expected) {
        $neg = new Negotiator();

        $matches = $this->call_private_method('Negotiation\Negotiator', 'findMatches', $neg, [$headerParts, $priorities]);

        $this->assertEquals($expected, $matches);
    }

    public static function dataProviderForTestFindMatches() {
        return [
            [
                [new Accept('text/html; charset=UTF-8'), new Accept('image/png; foo=bar; q=0.7'), new Accept('*/*; foo=bar; q=0.4')],
                [new Accept('text/html; charset=UTF-8'), new Accept('image/png; foo=bar'), new Accept('application/pdf')],
                [
                    new AcceptMatch(1.0, 111, 0),
                    new AcceptMatch(0.7, 111, 1),
                    new AcceptMatch(0.4, 1, 1),
                ],
            ],
            [
                [new Accept('text/html'), new Accept('image/*; q=0.7')],
                [new Accept('text/html; asfd=qwer'), new Accept('image/png'), new Accept('application/pdf')],
                [
                    new AcceptMatch(1.0, 110, 0),
                    new AcceptMatch(0.7, 100, 1),
                ],
            ],
            [ # https://tools.ietf.org/html/rfc7231#section-5.3.2
              [new Accept('text/*; q=0.3'), new Accept('text/html; q=0.7'), new Accept('text/html; level=1'), new Accept('text/html; level=2; q=0.4'), new Accept('*/*; q=0.5')],
              [new Accept('text/html; level=1'), new Accept('text/html'), new Accept('text/plain'), new Accept('image/jpeg'), new Accept('text/html; level=2'), new Accept('text/html; level=3')],
              [
                  new AcceptMatch(0.3, 100, 0),
                  new AcceptMatch(0.7, 110, 0),
                  new AcceptMatch(1.0, 111, 0),
                  new AcceptMatch(0.5, 0, 0),
                  new AcceptMatch(0.3, 100, 1),
                  new AcceptMatch(0.7, 110, 1),
                  new AcceptMatch(0.5, 0, 1),
                  new AcceptMatch(0.3, 100, 2),
                  new AcceptMatch(0.5, 0, 2),
                  new AcceptMatch(0.5, 0, 3),
                  new AcceptMatch(0.3, 100, 4),
                  new AcceptMatch(0.7, 110, 4),
                  new AcceptMatch(0.4, 111, 4),
                  new AcceptMatch(0.5, 0, 4),
                  new AcceptMatch(0.3, 100, 5),
                  new AcceptMatch(0.7, 110, 5),
                  new AcceptMatch(0.5, 0, 5),
              ],
            ],
        ];
    }
}
