<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\HttpMethod;
use Morpho\Testing\TestCase;

class HttpMethodTest extends TestCase {
    public function testIsValid() {
        $this->assertFalse(HttpMethod::isValid(''));
        $this->assertFalse(HttpMethod::isValid('some-invalid-value'));
        $this->assertTrue(HttpMethod::isValid('GET'));
        $this->assertFalse(HttpMethod::isValid('get'));
    }
}
