<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */

namespace Morpho\Base;

use OutOfRangeException;
use RuntimeException;
use UnexpectedValueException;

use function array_flip;
use function array_intersect_key;
use function array_keys;
use function count;

class Must {
    public static function beTruthy(mixed $val, string $message = null): mixed {
        if (!$val) {
            throw new MustException((string)$message, $val);
        }
        return $val;
    }

    /**
     * @param mixed $val
     * @return mixed @todo: change to null type
     */
    public static function beNull(mixed $val): mixed {
        // @todo: add $message argument
        self::beTruthy($val === null);
        return $val;
    }

    /**
     * @param mixed $val
     * @return mixed
     */
    public static function beNotNull(mixed $val): mixed {
        // @todo: add $message argument
        self::beTruthy($val !== null);
        return $val;
    }

    public static function beEmpty(mixed $val): mixed {
        // @todo: add $message argument
        self::beTruthy(empty($val), 'The value must be empty');
        return $val;
    }

    public static function beNotEmpty(mixed $val): mixed {
        // @todo: add $message
        self::beTruthy(!empty($val), 'The value must be non empty');
        return $val;
    }

    /**
     * @param             $haystack
     * @param             $needle
     * @param string|null $message
     * @return void
     */
    public static function contain($haystack, $needle, string $message = null): void {
        self::beTruthy(contains($haystack, $needle), $message ?? 'A haystack does not contain a needle');
    }

    public static function haveAtLeastKeys(array $arr, array $keys, string $message = null): array {
        $intersection = array_intersect_key(array_flip($keys), $arr);
        if (count($intersection) != count($keys)) {
            throw new MustException($message ?? 'The array must have the items with the specified keys', ['arr' => $arr, 'keys' => $keys]);
        }
        return $arr;
    }

    public static function haveExactKeys(array $arr, array $keys, string $message = null, bool $ordered = true): array {
        if (!$ordered) {
            $invalid = !empty(symDiff(array_keys($arr), $keys));
        } else {
            $invalid = array_keys($arr) !== $keys;
        }
        if ($invalid) {
            throw new MustException($message ?? 'The array must have exactly the folllowing keys and no other items', ['arr' => $arr, 'keys' => $keys]);
        }
        return $arr;
    }
}
