<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\HostNameValidator;
use Morpho\Testing\TestCase;

use function strtolower;

class HostNameValidatorTest extends TestCase {
    public static function dataCurrentHostName_ValidIps() {
        return [
            // IPv4
            [
                '192.0.2.3',
                '192.0.2.3',
            ],
            [
                '192.0.2.3',
                '192.0.2.3:1234',
            ],
            // IPv6, some cases found in OpenJDK and RFCs.
            [
                '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]',
                '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80',
            ],
            [
                '[1080:0:0:0:8:800:200C:417A]',
                '[1080:0:0:0:8:800:200C:417A]',
            ],
            [
                '[3ffe:2a00:100:7031::1]',
                '[3ffe:2a00:100:7031::1]',
            ],
            [
                '[1080::8:800:200C:417A]',
                '[1080::8:800:200C:417A]',
            ],
            [
                '[::192.9.5.5]',
                '[::192.9.5.5]',
            ],
            [
                '[::FFFF:129.144.52.38]',
                '[::FFFF:129.144.52.38]:80',
            ],
            [
                '[2010:836B:4179::836B:4179]',
                '[2010:836B:4179::836B:4179]',
            ],
            [
                '[::1]',
                '[::1]',
            ],
        ];
    }

    /**
     * @dataProvider dataCurrentHostName_ValidIps
     */
    public function testCurrentHostName_ValidIps(string $expected, string $ip) {
        $_SERVER['HTTP_HOST'] = $ip;
        $validator = new HostNameValidator([$ip]);
        $this->assertSame(strtolower($expected), $validator->currentHostName());
    }

    public static function dataCurrentHostName_InvalidIps() {
        return [
            // Some cases found in OpenJDK and RFCs.
            [
                '[::foo',
            ],
            [
                "[foo",
            ],
            [
                'www.[]',
            ],
            [
                '[]',
            ],
            [
                '[].',
            ],
            [
                '[].www',
            ],
            [
                '[].www:80',
            ],
        ];
    }

    /**
     * @dataProvider dataCurrentHostName_InvalidIps
     */
    public function testCurrentHostName_InvalidIps(string $ip) {
        $_SERVER['HTTP_HOST'] = $ip;
        $validator = new HostNameValidator([$ip]);
        $this->assertFalse($validator->currentHostName());
    }
}
