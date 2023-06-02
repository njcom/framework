<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Base;

use function pow;
use function preg_replace;
use function round;
use function stripos;

class Converter {
    /**
     * The code was taken from [Bytes::toInt()](https://github.com/drupal/core-utility/blob/8.7.x/Bytes.php)
     * @param string $valWithUnit
     * @return int
     */
    public static function toBytes(string $valWithUnit): int {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $valWithUnit);
        $val = preg_replace('/[^0-9\.]/', '', $valWithUnit);
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            $b = round($val * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            $b = round($val);
        }
        return (int) $b;
    }
}
