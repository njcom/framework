<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\StatusCode;
use Morpho\Testing\TestCase;

class StatusCodeTest extends TestCase {
    public function testReason() {
        $codes = [
            429 => 'Too Many Requests',
            413 => 'Payload Too Large',
            403 => 'Forbidden',
            200 => 'OK',
            302 => 'Found',
        ];
        foreach ($codes as $code => $reason) {
            $this->assertSame($reason, StatusCode::from($code)->reason());
        }
    }
}
