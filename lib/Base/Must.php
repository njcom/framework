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

use function array_diff_key;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function count;
use function implode;

class Must {
    /**
     * @param mixed       $result
     * @param string|null $errMessage
     * @return void
     * @todo: add tests
     */
    public static function beTruthy(mixed $result, string $errMessage = null): void {
        $result = (bool)$result;
        if (false === $result) {
            throw new RuntimeException((string)$errMessage);
        }
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

    public static function beNotEmpty(mixed $val): mixed {
        self::beTruthy(!empty($v), 'The value must be non empty');
        return $val;
    }

    public static function beEmpty(mixed $val): mixed {
        self::beTruthy($val, 'The value must be empty');
        return $val;
    }

    public static function haveOnlyKeys(array $arr, array $allowedKeys): array {
        $diff = array_diff_key($arr, array_flip($allowedKeys));
        if (count($diff)) {
            throw new RuntimeException(
                'Not allowed items are present: ' . shorten(implode(', ', array_keys($diff)), 80)
            );
        }
        return $arr;
    }

    public static function haveItems(
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
    }

    public static function haveKeys(array $arr, array $requiredKeys): void {
        $intersection = array_intersect_key(array_flip($requiredKeys), $arr);
        if (count($intersection) != count($requiredKeys)) {
            throw new RuntimeException("Required items are missing");
        }
    }

    public static function beInRange($value, $start, $end): void {
        if ($value < $start || $value > $end) {
            throw new OutOfRangeException("The value $value is out of range");
        }
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
}
