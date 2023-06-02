<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use Morpho\Base\Timer;
use Morpho\Testing\TestCase;

use function is_float;
use function usleep;

class TimerTest extends TestCase {
    public function testTime() {
        $timer = new Timer();

        usleep(10 * 1000);  // Wait 10 ms

        $time = $timer->diff(false);
        $this->assertTrue(is_float($time));
        // It seems like usleep() and other time functions don't return
        // valid result, so we use half of value == 0.010/2.
        $this->assertTrue($time - 0.005 >= -PHP_FLOAT_EPSILON);  // At least 5 ms are passed?
    }
}
