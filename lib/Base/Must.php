<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
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
    public static function beTruthy(mixed $val, string $errorMessage = null): mixed {
        if (!$val) {
            throw new MustException((string)$errorMessage);
        }
        return $val;
    }

    /**
     * @param mixed $val
     * @return mixed @todo: change to null type
     */
    public static function beNull(mixed $val): mixed {
        self::beTruthy($val === null);
        return $val;
    }

    /**
     * @param mixed $val
     * @return mixed
     */
    public static function beNotNull(mixed $val): mixed {
        self::beTruthy($val !== null);
        return $val;
    }

    public static function beEmpty(mixed $val): mixed {
        self::beTruthy(empty($val), 'The value must be empty');
        return $val;
    }

    public static function beNotEmpty(mixed $val): mixed {
        self::beTruthy(!empty($val), 'The value must be non empty');
        return $val;
    }

    /**
     * @param             $haystack
     * @param             $needle
     * @param string|null $errMessage
     * @return void
     */
    public static function contain($haystack, $needle, string $errMessage = null): void {
        self::beTruthy(contains($haystack, $needle), $errMessage ?: 'A haystack does not contain a needle');
    }

    public static function haveAtLeastKeys(array $arr, array $keys): array {
        $intersection = array_intersect_key(array_flip($keys), $arr);
        if (count($intersection) != count($keys)) {
            throw new MustException('The array must have the items with the specified keys');
        }
        return $arr;
    }

    public static function haveExactKeys(array $arr, array $keys, bool $ordered = false): array {
        if (!$ordered) {
            $invalid = !empty(symDiff(array_keys($arr), $keys));
        } else {
            $invalid = array_keys($arr) !== $keys;
        }
        if ($invalid) {
            throw new MustException('The array must have the items with the specified keys and no other items');
        }
        return $arr;
    }

/*    public static function haveItems(
        array $arr,
        array $requiredKeys,
        bool $returnOnlyRequired = true,
        bool $checkForEmptiness = false
    ): array {
        $newArr = [];
        foreach ($requiredKeys as $key) {
            if (!isset($arr[$key]) && !array_key_exists($key, $arr)) {
                throw new UnexpectedValueException("Missing the required item with the key " . $key);
            }
            if ($checkForEmptiness && !$arr[$key]) {
                throw new UnexpectedValueException("The item '$key' is empty");
            }
            $newArr[$key] = $arr[$key];
        }
        return $returnOnlyRequired ? $newArr : $arr;
    }*/

/*    public static function beInRange(int $val, int $start, int $end): void {
        if ($val < $start || $val > $end) {
            throw new OutOfRangeException("The value $val is out of range");
        }
    }*/
}
