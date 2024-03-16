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

namespace Morpho\Test\Unit\App\Web\Negotiation;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use ReflectionMethod;

abstract class TestCase extends PHPUnitTestCase {
    protected function call_private_method($class, $method, $object, $params) {
        $method = new ReflectionMethod($class, $method);

        $method->setAccessible(true);

        return $method->invokeArgs($object, $params);
    }
}
