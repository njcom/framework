<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Uri;

trait TUriParserDataProvider {
    public static function dataParse() {
        return [
            [
                '',
                [
                    'scheme'    => '',
                    'authority' => null,
                    'path'      => '',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            // Cases for authority
            [
                'http://localhost/', // ends with '/'
                [
                    'scheme'    => 'http',
                    'authority' => 'localhost',
                    'path'      => '/',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            [
                'http://localhost?', // ends with '?'
                [
                    'scheme'    => 'http',
                    'authority' => 'localhost',
                    'path'      => '',
                    'query'     => '',
                    'fragment'  => null,
                ],
            ],
            [
                'http://localhost#', // ends with '#'
                [
                    'scheme'    => 'http',
                    'authority' => 'localhost',
                    'path'      => '',
                    'query'     => null,
                    'fragment'  => '',
                ],
            ],
            [
                'http://localhost',  // ends with end of URI
                [
                    'scheme'    => 'http',
                    'authority' => 'localhost',
                    'path'      => '',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            // Cases for path
            [
                'foo://info.example.com?fred',
                [
                    'scheme'    => 'foo',
                    'authority' => 'info.example.com',
                    'path'      => '',
                    'query'     => 'fred',
                    'fragment'  => null,
                ],
            ],
            [
                'foo://info.example.com/system/module#test',
                [
                    'scheme'    => 'foo',
                    'authority' => 'info.example.com',
                    'path'      => '/system/module',
                    'query'     => null,
                    'fragment'  => 'test',
                ],
            ],
            [
                '/foo/bar/baz',
                [
                    'scheme'    => '',
                    'authority' => null,
                    'path'      => '/foo/bar/baz',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            [
                './this:that',
                [
                    'scheme'    => '',
                    'authority' => null,
                    'path'      => './this:that',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            // Modified samples from RFC 3986
            [
                'http://www.ics.uci.edu/pub/ietf/uri/?foo=bar&baz=quak#Related',
                [
                    'scheme'    => 'http',
                    'authority' => 'www.ics.uci.edu',
                    'path'      => '/pub/ietf/uri/',
                    'query'     => 'foo=bar&baz=quak',
                    'fragment'  => 'Related',
                ],
            ],
            // Samples from RFC 3986
            [
                'ftp://ftp.is.co.za/rfc/rfc1808.txt',
                [
                    'scheme'    => 'ftp',
                    'authority' => 'ftp.is.co.za',
                    'path'      => '/rfc/rfc1808.txt',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            [
                'http://www.ietf.org/rfc/rfc2396.txt',
                [
                    'scheme'    => 'http',
                    'authority' => 'www.ietf.org',
                    'path'      => '/rfc/rfc2396.txt',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            [
                'ldap://[2001:db8::7]/c=GB?objectClass?one',
                [
                    'scheme'    => 'ldap',
                    'authority' => '[2001:db8::7]',
                    'path'      => '/c=GB',
                    'query'     => 'objectClass?one',
                    'fragment'  => null,
                ],
            ],
            [
                'mailto:John.Doe@example.com',
                [
                    'scheme'    => 'mailto',
                    'authority' => null,
                    'path'      => 'John.Doe@example.com',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            [
                'news:comp.infosystems.www.servers.unix',
                [
                    'scheme'    => 'news',
                    'authority' => null,
                    'path'      => 'comp.infosystems.www.servers.unix',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            [
                'tel:+1-816-555-1212',
                [
                    'scheme'    => 'tel',
                    'authority' => null,
                    'path'      => '+1-816-555-1212',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            [
                'telnet://192.0.2.16:80/',
                [
                    'scheme'    => 'telnet',
                    'authority' => '192.0.2.16:80',
                    'path'      => '/',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            [
                'urn:oasis:names:specification:docbook:dtd:xml:4.1.2',
                [
                    'scheme'    => 'urn',
                    'authority' => null,
                    'path'      => 'oasis:names:specification:docbook:dtd:xml:4.1.2',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            [
                'http://a/b/c/d;p?q',
                [
                    'scheme'    => 'http',
                    'authority' => 'a',
                    'path'      => '/b/c/d;p',
                    'query'     => 'q',
                    'fragment'  => null,
                ],
            ],
            [
                // A traditional file URI for a local file with an empty authority.
                'file:///home/user/.vimrc',
                [
                    'scheme'    => 'file',
                    'authority' => '', // empty authority
                    'path'      => '/home/user/.vimrc',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            [
                '//example.com',
                [
                    'scheme'    => '',
                    'authority' => 'example.com',
                    'path'      => '',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            // Samples from RFC 8089:
            [
                // The minimal representation of a local file with no authority field and an absolute path that begins with a slash "/".
                'file:/path/to/file',
                [
                    'scheme'    => 'file',
                    'authority' => null, // no authority
                    'path'      => '/path/to/file',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            [
                // The minimal representation of a local file with no authority field and an absolute path that begins with a slash "/".
                'file://host.example.com/path/to/file',
                [
                    'scheme'    => 'file',
                    'authority' => 'host.example.com',
                    'path'      => '/path/to/file',
                    'query'     => null,
                    'fragment'  => null,
                ],
            ],
            // Other cases
            [
                'foo://example.com:8042/over/there?name=ferret#nose',
                [
                    'scheme'    => 'foo',
                    'authority' => 'example.com:8042',
                    'path'      => '/over/there',
                    'query'     => 'name=ferret',
                    'fragment'  => 'nose',
                ],
            ],
            [
                'http://привет.мир/базовый/путь?первый=123&второй=quak#таблица-1',
                [
                    'scheme'    => 'http',
                    'authority' => 'привет.мир',
                    'path'      => '/базовый/путь',
                    'query'     => 'первый=123&второй=quak',
                    'fragment'  => 'таблица-1',
                ],
            ],
            [
                // 4.4.  Same-Document Reference
                '#foo',
                [
                    'scheme'    => '',
                    'authority' => null,
                    'path'      => '',
                    'query'     => null,
                    'fragment'  => 'foo',
                ],
            ],
        ];
    }
}
