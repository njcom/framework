<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Base;

use InvalidArgumentException;

/**
 * Pseudorandom number generator (PRNG)
 * Modified file from Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
abstract class Rand {
    public static function randBool(): bool {
        $byte = random_bytes(1);
        return (bool) (ord($byte) % 2);
    }

    /**
     * Generate random float [0..1)
     * This function generates floats with platform-dependent precision
     *
     * PHP uses double precision floating-point format (64-bit) which has
     * 52-bits of significand precision. We gather 7 bytes of random data,
     * and we fix the exponent to the bias (1023). In this way we generate
     * a float of 1.mantissa.
     */
    public static function randFloat(): float {
        $bytes = random_bytes(7);
        $bytes[6] = $bytes[6] | chr(0xf0);
        $bytes .= chr(63);
        // exponent bias (1023)
        [, $float] = unpack('d', $bytes);
        return $float - 1;
    }

    /**
     * Generate a random string of specified length.
     *
     * Uses supplied character list for generating the new string.
     * If no character list provided - uses Base 64 character set.
     */
    public static function randStr(int $length, string $charlist = null): string {
        if ($length < 1) {
            throw new InvalidArgumentException('Length should be >= 1');
        }
        // charlist is empty or not provided
        if (empty($charlist)) {
            $numBytes = ceil($length * 0.75);
            $bytes = random_bytes((int) $numBytes);
            return mb_substr(rtrim(base64_encode($bytes), '='), 0, $length, '8bit');
        }
        $listLen = mb_strlen($charlist, '8bit');
        if ($listLen == 1) {
            return str_repeat($charlist, $length);
        }
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $pos = self::randInt(0, $listLen - 1);
            $result .= $charlist[$pos];
        }
        return $result;
    }

    /**
     * Generate a random integer between $min and $max
     */
    public static function randInt(int $min, int $max): int {
        return random_int($min, $max);
    }
}