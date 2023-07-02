<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\DataProcessing;

use function floatval;
use function is_scalar;
use function preg_match;
use function preg_replace;
use function str_replace;
use function strlen;

class PriceFilter {
    public function __invoke(mixed $val): ?float {
        if (!is_scalar($val)) {
            return null;
        }
        $val = str_replace(',', '.', (string) $val);
        $search = ['{\\.+}si', '{[^-\\d.]}si'];
        $replace = ['.', ''];
        $val = preg_replace($search, $replace, $val);
        if (!strlen($val) || !self::isFloat($val)) {
            return null;
        }
        return floatval($val);
    }

    private static function isFloat(string $value): bool {
        // @TODO: ['+'|'-'] [digit* '.'] digit+ [('e'|'E') ['+'|'-'] digit+]
        return (bool) preg_match('{^[-+]?[0-9]+(?:\\.[0-9]*)?$}is', $value);
    }
}