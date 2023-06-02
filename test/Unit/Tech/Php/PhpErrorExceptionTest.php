<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\Php;

use Morpho\Tech\Php\WarningException;
use Morpho\Testing\TestCase;

class PhpErrorExceptionTest extends TestCase {
    public function testToString() {
        $e = new WarningException("My message", 0, E_WARNING);
        $re = "/^Morpho\\\\Tech\\\\Php\\\\WarningException \\(E_WARNING\\): My message/si";
        $this->assertMatchesRegularExpression($re, $e->__toString());
    }
}