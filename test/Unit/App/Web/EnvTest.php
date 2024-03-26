<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\Env;
use Morpho\App\Web\HttpVersion;
use Morpho\Testing\TestCase;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;

#[BackupGlobals(enabled: true)]
class EnvTest extends TestCase {
    public static function dataHttpVersion(): iterable {
        yield [
            'HTTP/1.1',
            HttpVersion::V1_1,
        ];
        yield [
            'HTTP/2',
            HttpVersion::V2,
        ];
        yield [
            'HTTP/invalid',
            HttpVersion::V1_1,
        ];
        yield [
            'invalid',
            HttpVersion::V1_1,
        ];
        yield [
            'HTTP/10.1',
            HttpVersion::V1_1,
        ];
    }

    #[DataProvider('dataHttpVersion')]
    public function testHttpVersion(string $serverProtocol, HttpVersion $expected) {
        $_SERVER['SERVER_PROTOCOL'] = $serverProtocol;
        $this->assertSame($expected, Env::httpVersion());
    }
}
