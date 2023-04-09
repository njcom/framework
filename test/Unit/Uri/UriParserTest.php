<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Uri;

use Morpho\Uri\Authority;
use Morpho\Uri\UriParser;
use Morpho\Testing\TestCase;

class UriParserTest extends TestCase {
    use TUriParserDataProvider;

    /**
     * @dataProvider dataParse
     */
    public function testParse(string $uriStr, array $expected) {
        $this->checkParse($uriStr, $expected);
    }

    private function checkParse(string $uriStr, array $expected): void {
        $uri = (new UriParser())->__invoke($uriStr);

        $this->assertSame($expected['scheme'], $uri->scheme());

        if (null === $expected['authority']) {
            $this->assertTrue($uri->authority()->isNull());
        } else {
            $this->assertSame($expected['authority'], $uri->authority()->toStr(false));
        }

        $this->assertSame($expected['path'], $uri->path()->toStr(false));

        if (null === $expected['query']) {
            $this->assertTrue($uri->query()->isNull());
        } else {
            $this->assertSame($expected['query'], $uri->query()->toStr(false));
        }

        $this->assertSame($expected['fragment'], $uri->fragment());
    }

    public static function dataParseOnlyAuthority_ValidCases() {
        yield [
            '',
            [
                'userInfo' => '',
                'host'     => '',
                'port'     => null,
            ],
        ];
        yield [
            'user:password@[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80',
            [
                'userInfo' => 'user:password',
                'host'     => '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]',
                'port'     => 80,
            ],
        ];
        yield [
            'user:pass^word@[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]',
            [
                'userInfo' => 'user:pass^word',
                'host'     => '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]',
                'port'     => null,
            ],
        ];
        yield [
            '127.0.0.1',
            [
                'userInfo' => null,
                'host'     => '127.0.0.1',
                'port'     => null,
            ],
        ];
        yield [
            '127.0.0.1:80',
            [
                'userInfo' => null,
                'host'     => '127.0.0.1',
                'port'     => 80,
            ],
        ];
        yield [
            '@127.0.0.1:80',
            [
                'userInfo' => '',
                'host'     => '127.0.0.1',
                'port'     => 80,
            ],
        ];

        // IPv6, some cases found in OpenJDK and RFCs.
        yield [
            '[1080:0:0:0:8:800:200C:417A]',
            [
                'userInfo' => null,
                'host'     => '[1080:0:0:0:8:800:200C:417A]',
                'port'     => null,
            ],
        ];
        yield [
            '[3ffe:2a00:100:7031::1]',
            [
                'userInfo' => null,
                'host'     => '[3ffe:2a00:100:7031::1]',
                'port'     => null,
            ],
        ];
        yield [
            '[1080::8:800:200C:417A]',
            [
                'userInfo' => null,
                'host'     => '[1080::8:800:200C:417A]',
                'port'     => null,
            ],
        ];
        yield [
            '[::192.9.5.5]',
            [
                'userInfo' => null,
                'host'     => '[::192.9.5.5]',
                'port'     => null,
            ],
        ];
        yield [
            '[::FFFF:129.144.52.38]',
            [
                'userInfo' => null,
                'host'     => '[::FFFF:129.144.52.38]',
                'port'     => null,
            ],
        ];
        yield [
            '[::FFFF:129.144.52.38]:80',
            [
                'userInfo' => null,
                'host'     => '[::FFFF:129.144.52.38]',
                'port'     => 80,
            ],
        ];
        yield [
            '[2010:836B:4179::836B:4179]',
            [
                'userInfo' => null,
                'host'     => '[2010:836B:4179::836B:4179]',
                'port'     => null,
            ],
        ];
        yield [
            '[::1]',
            [
                'userInfo' => null,
                'host'     => '[::1]',
                'port'     => null,
            ],
        ];
    }

    /**
     * @dataProvider dataParseOnlyAuthority_ValidCases
     */
    public function testParseOnlyAuthority_ValidCases($authority, $expected) {
        $authority = UriParser::parseOnlyAuthority($authority);
        $this->assertSame($expected['userInfo'], $authority->userInfo());
        $this->assertSame($expected['host'], $authority->host());
        $this->assertSame($expected['port'], $authority->port());
    }

    public static function dataParseOnlyAuthority_InvalidCases() {
        yield [
            '',
        ];
    }

    /**
     * @dataProvider dataParseOnlyAuthority_InvalidCases
     */
    public function testParseOnlyAuthority_InvalidCases($authority) {
        $this->assertEquals(new Authority(), UriParser::parseOnlyAuthority($authority));
    }

    public function testParseTheSameUriTwice() {
        $uriStr = 'foo://example.com:8042/over/there?name=ferret#nose';
        $uriParser = new UriParser();
        for ($i = 0; $i < 2; $i++) {
            $uri = $uriParser->__invoke($uriStr);
            $this->assertSame('foo', $uri->scheme());
            $this->assertSame('example.com:8042', $uri->authority()->toStr(false));
            $this->assertSame('/over/there', $uri->path()->toStr(false));
            $this->assertSame('name=ferret', $uri->query()->toStr(false));
            $this->assertSame('nose', $uri->fragment());
        }
    }

    public static function dataParseOnlyQuery() {
        yield [
            'foo',
            ['foo' => null],
        ];
        yield [
            'foo=',
            ['foo' => ''],
        ];
        yield [
            'foo=bar&baz=test',
            [
                'foo' => 'bar',
                'baz' => 'test',
            ],
        ];
        yield [
            'foo=bar&foo=baz',
            [
                'foo' => 'baz',
            ],
        ];
        yield [
            'arr[]=foo&arr[]=bar',
            [
                'arr' => [
                    'foo',
                    'bar',
                ],
            ],
        ];
        yield [
            'first=value&arr[]=foo bar&arr[test]=baz',
            [
                'first' => 'value',
                'arr'   => [
                    'foo bar',
                    'test' => 'baz',
                ],
            ],
        ];
        yield [
            'foo[bar][baz]',
            [
                'foo' => [
                    'bar' => [
                        'baz' => null,
                    ],
                ],
            ],
        ];
        yield [
            'foo[bar][baz]=qux',
            [
                'foo' => [
                    'bar' => [
                        'baz' => 'qux',
                    ],
                ],
            ],
        ];
        yield [
            'foo[][baz]',
            [
                'foo' => [
                    [
                        'baz' => null,
                    ],
                ],
            ],
        ];
        yield [
            'foo[][baz]=bar',
            [
                'foo' => [
                    [
                        'baz' => 'bar',
                    ],
                ],
            ],
        ];
        yield [
            'foo[][]=test',
            [
                'foo' => [
                    [
                        'test',
                    ],
                ],
            ],
        ];
        yield [
            '[]&foo=bar',
            [
                'foo' => 'bar',
            ],
        ];
        yield [
            '[][]&foo=bar',
            [
                'foo' => 'bar',
            ],
        ];
        yield [
            '[][]=test&foo=bar',
            [
                'foo' => 'bar',
            ],
        ];
        yield [
            '=test&foo=bar', // no name
            [
                'foo' => 'bar',
            ],
        ];
        yield [
            '=&test=123', // no name and value
            [
                'test' => '123',
            ],
        ];
        yield [
            'name=&test=123', // no value
            [
                'name' => '',
                'test' => '123',
            ],
        ];
        yield [
            '==&test=123', // two `=` chars, no name and value for both
            [
                'test' => '123',
            ],
        ];
        yield [
            'foo[[&test=123',
            [
                'test' => '123',
            ],
        ];
        yield [
            'foo][&test=123',
            [
                'test' => '123',
            ],
        ];
        yield [
            'foo[&test=123',
            [
                'test' => '123',
            ],
        ];
        yield [
            'foo]&test=123',
            [
                'test' => '123',
            ],
        ];
    }

    /**
     * @dataProvider dataParseOnlyQuery
     */
    public function testParseOnlyQuery($queryStr, $expected) {
        $query = UriParser::parseOnlyQuery($queryStr);
        $this->assertSame($expected, $query->getArrayCopy());
    }
}
