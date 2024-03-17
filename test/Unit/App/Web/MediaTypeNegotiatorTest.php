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
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\MediaTypeNegotiator;
use Morpho\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MediaTypeNegotiatorTest extends TestCase {
    private MediaTypeNegotiator $negotiator;

    protected function setUp(): void {
        parent::setUp();
        $this->negotiator = new MediaTypeNegotiator();
    }

    public static function dataDetectBest(): iterable {
        $pearAcceptHeader = 'text/html,application/xhtml+xml,application/xml;q=0.9,text/*;q=0.7,*/*,image/gif; q=0.8, image/jpeg; q=0.6, image/*';
        $rfcHeader = 'text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5';

        return [
            // Errors
            ['sdlfkj20ff; wdf', ['foo/qwer'], false],
            ['/qwer', ['f/g'], false],
            ['/qwer,f/g', ['f/g'], ['f/g', []]],
            ['foo/bar', ['/qwer'], false], //'Invalid media type'
            ['', ['foo/bar'], false], //'The header string should not be empty'
            ['*/*', [], false], //'A set of server priorities should be given'

            // See: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
            [$rfcHeader, ['text/html;level=1'], ['text/html', ['level' => '1']]],
            [$rfcHeader, ['text/html'], ['text/html', []]],
            [$rfcHeader, ['text/plain'], ['text/plain', []]],
            [$rfcHeader, ['image/jpeg',], ['image/jpeg', []]],
            [$rfcHeader, ['text/html;level=2'], ['text/html', ['level' => '2']]],
            [$rfcHeader, ['text/html;level=3'], ['text/html', ['level' => '3']]],

            ['text/html,text/*;q=0.7', ['text/html;q=0.5', 'text/plain;q=0.9'], ['text/plain', []]],
            ['text/*;q=0.7, text/html;q=0.3, */*;q=0.5, image/png;q=0.4', ['text/html', 'image/png'], ['image/png', []]],
            ['image/png;q=0.1, text/plain, audio/ogg;q=0.9', ['image/png', 'text/plain', 'audio/ogg'], ['text/plain', []]],
            ['image/png, text/plain, audio/ogg', ['baz/asdf'], false],
            ['image/png, text/plain, audio/ogg', ['audio/ogg'], ['audio/ogg', []]],
            ['image/png, text/plain, audio/ogg', ['YO/SuP'], false],
            ['text/html; charset=UTF-8, application/pdf', ['text/html; charset=UTF-8'], ['text/html', ['charset' => 'UTF-8']]],
            ['text/html; charset=UTF-8, application/pdf', ['text/html'], false],
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
            ['text/html', ['application/rss'], false],
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

    #[DataProvider('dataDetectBest')]
    public function testDetectBest(string $acceptHeaderValue, array $priorities, $expected) {
        $bestMediaRange = $this->negotiator->detectBest($acceptHeaderValue, $priorities);
        if (is_array($expected)) {
            $this->assertSame($expected[0], $bestMediaRange['type']);
            $this->assertSame($expected[1], $bestMediaRange['parameters']);
        } else {
            $this->assertSame($expected, $bestMediaRange);
        }
    }

    public static function dataParseAcceptHeaderValue(): iterable {
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

    #[DataProvider('dataParseAcceptHeaderValue')]
    public function testParseAcceptHeaderValue(string $headerValue, array $expected) {
        $parsed = $this->negotiator->parseAcceptHeaderValue($headerValue);
        $this->assertSame($expected, $parsed);
    }

    public static function dataFindMatches(): iterable {
        yield [
            ['text/html; charset=UTF-8', 'image/png; foo=bar; q=0.7', '*/*; foo=bar; q=0.4'],
            ['text/html; charset=UTF-8', 'image/png; foo=bar', 'application/pdf'],
            [
                ['quality' => 1.0, 'score' => 111, 'index' => 0],
                ['quality' => 0.7, 'score' => 111, 'index' => 1],
                ['quality' => 0.4, 'score' => 1, 'index' => 1],
            ],
        ];

        yield [
            ['text/html', 'image/*; q=0.7'],
            ['text/html; asfd=qwer', 'image/png', 'application/pdf'],
            [
                ['quality' => 1.0, 'score' => 110, 'index' => 0],
                ['quality' => 0.7, 'score' => 100, 'index' => 1],
            ],
        ];
        yield [
            # https://tools.ietf.org/html/rfc7231#section-5.3.2
            ['text/*; q=0.3', 'text/html; q=0.7', 'text/html; level=1', 'text/html; level=2; q=0.4', '*/*; q=0.5'],
            ['text/html; level=1', 'text/html', 'text/plain', 'image/jpeg', 'text/html; level=2', 'text/html; level=3'],
            [
                ['quality' => 0.3, 'score' => 100, 'index' => 0],
                ['quality' => 0.7, 'score' => 110, 'index' => 0],
                ['quality' => 1.0, 'score' => 111, 'index' => 0],
                ['quality' => 0.5, 'score' => 0, 'index' => 0],
                ['quality' => 0.3, 'score' => 100, 'index' => 1],
                ['quality' => 0.7, 'score' => 110, 'index' => 1],
                ['quality' => 0.5, 'score' => 0, 'index' => 1],
                ['quality' => 0.3, 'score' => 100, 'index' => 2],
                ['quality' => 0.5, 'score' => 0, 'index' => 2],
                ['quality' => 0.5, 'score' => 0, 'index' => 3],
                ['quality' => 0.3, 'score' => 100, 'index' => 4],
                ['quality' => 0.7, 'score' => 110, 'index' => 4],
                ['quality' => 0.4, 'score' => 111, 'index' => 4],
                ['quality' => 0.5, 'score' => 0, 'index' => 4],
                ['quality' => 0.3, 'score' => 100, 'index' => 5],
                ['quality' => 0.7, 'score' => 110, 'index' => 5],
                ['quality' => 0.5, 'score' => 0, 'index' => 5],
            ],
        ];
    }

    #[DataProvider('dataFindMatches')]
    public function testFindMatches(array $mediaRanges, array $preferredMediaRanges, array $expected) {
        $mediaRanges = array_map(MediaTypeNegotiator::parseMediaRange(...), $mediaRanges);
        $preferredMediaRanges = array_map(MediaTypeNegotiator::parseMediaRange(...), $preferredMediaRanges);
        $matches = MediaTypeNegotiator::findMatches($mediaRanges, $preferredMediaRanges);
        $this->assertEquals($expected, $matches);
    }
}
