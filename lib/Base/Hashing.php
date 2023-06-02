<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Base;

class Hashing {
    public static function isMd5Like(string $testString): bool {
        if (!isset($testString[0])) {
            return false;
        }
        return (bool) preg_match('~^[a-fA-F\\d]{32}$~s', $testString);
    }
}