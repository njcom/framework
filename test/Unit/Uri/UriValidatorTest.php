<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Uri;

use Morpho\Uri\UriValidator;
use Morpho\Testing\TestCase;

use function print_r;

class UriValidatorTest extends TestCase {
    public static function dataValidateScheme() {
        return [
            [
                '',
                false,
            ],
            [
                'http',
                true,
            ],
            [
                'HTTP',
                true,
            ],
            [
                'h',
                true,
            ],
            [
                'H',
                true,
            ],
            [
                'q^u',
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataValidateScheme
     */
    public function testValidateScheme(string $scheme, bool $isValid) {
        $this->assertSame($isValid, UriValidator::validateScheme($scheme));
    }

    public static function dataValidateAuthority() {
        yield [
            'user:pass^word@[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80',
            false,
        ];
        yield [
            'user:password@[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80',
            true,
        ];
        yield [
            'localhost',
            true,
        ];
        yield [
            'localhost:80',
            true,
        ];
        yield [
            // The reg-name syntax allows percent-encoded octets in order to represent non-ASCII registered names... Non-ASCII characters must first be encoded according to UTF-8 [STD63], and then each octet of the corresponding UTF-8 sequence must be percent-encoded to be represented as URI characters.
            // rawurlencode('локалхост')
            '%D0%BB%D0%BE%D0%BA%D0%B0%D0%BB%D1%85%D0%BE%D1%81%D1%82',
            true,
        ];
        yield [
            // локалхост:80
            '%D0%BB%D0%BE%D0%BA%D0%B0%D0%BB%D1%85%D0%BE%D1%81%D1%82:80',
            true,
        ];
        yield [
            // локалхост encoded
            'локалхост',
            false,
        ];
    }

    /**
     * @dataProvider dataValidateAuthority
     */
    public function testValidateAuthority(string $authority, bool $isValid) {
        $this->assertSame($isValid, UriValidator::validateAuthority($authority));
    }

    public static function dataValidatePath_WithAuthorityCase() {
        yield from self::validatePathSamples();
        yield ['', true];
        yield ['//', true];
    }

    /**
     * @dataProvider dataValidatePath_WithAuthorityCase
     */
    public function testValidatePath_WithAuthorityCase(string $path, bool $isValid) {
        $this->validatePath($path, $isValid, true);
    }

    public static function dataValidatePath_WithoutAuthorityCase(): iterable {
        yield from self::validatePathSamples();
        yield ['', false];
        yield ['//', false];
        /*
                $this->assertTrue(UriValidator::validatePath('fred@example.com'));
                $this->assertTrue(UriValidator::validatePath('John.Doe@example.com'));
                $this->assertTrue(UriValidator::validatePath('comp.infosystems.www.servers.unix'));
                $this->assertTrue(UriValidator::validatePath('+1-816-555-1212'));
                $this->assertTrue(UriValidator::validatePath('oasis:names:specification:docbook:dtd:xml:4.1.2'));
        */
    }

    /**
     * @dataProvider dataValidatePath_WithoutAuthorityCase
     */
    public function testValidatePath_WithoutAuthorityCase(string $path, bool $isValid) {
        $this->validatePath($path, $isValid, false);
    }

    private function validatePath(string $path, bool $isValid, bool $hasAuthority): void {
        if ($isValid) {
            $this->assertTrue(UriValidator::validatePath($path, $hasAuthority), 'Path: ' . print_r($path, true));
        } else {
            $this->assertFalse(UriValidator::validatePath($path, $hasAuthority), 'Path: ' . print_r($path, true));
        }
    }

    private static function validatePathSamples(): iterable {
        yield ['/', true];
        yield ['/c=GB', true];
        yield ['/over/there', true];
        yield ['/базовый/путь', false];
        yield ['/%D0%B1%D0%B0%D0%B7%D0%BE%D0%B2%D1%8B%D0%B9/%D0%BF%D1%83%D1%82%D1%8C', true];
    }
}
