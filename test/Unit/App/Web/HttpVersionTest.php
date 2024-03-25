<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\HttpVersion;
use Morpho\Testing\TestCase;

class HttpVersionTest extends TestCase {
    public function testIsValid() {
        $this->assertFalse(HttpVersion::isValid(''));
        $this->assertFalse(HttpVersion::isValid('some-invalid-value'));
        $this->assertTrue(HttpVersion::isValid('2'));
        $this->assertFalse(HttpVersion::isValid('2.0'));
        $this->assertFalse(HttpVersion::isValid('2.1'));
        $this->assertFalse(HttpVersion::isValid('2.7'));
        $this->assertTrue(HttpVersion::isValid('1.1'));
        $this->assertTrue(HttpVersion::isValid('3'));
        $this->assertFalse(HttpVersion::isValid('3.0'));
        $this->assertFalse(HttpVersion::isValid('3.1'));
        $this->assertFalse(HttpVersion::isValid('2.3.4'));
        $this->assertFalse(HttpVersion::isValid('4'));
        $this->assertFalse(HttpVersion::isValid('4.0'));
        $this->assertFalse(HttpVersion::isValid('4.1'));
    }
}
