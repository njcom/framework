<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\DataProcessing;

use Morpho\Base\IFn;

use function floatval;
use function is_scalar;
use function preg_match;
use function preg_replace;
use function str_replace;
use function strlen;

class PriceFilter implements IFn {
    public function __invoke(mixed $value): mixed {
        if (!is_scalar($value)) {
            return null;
        }
        $value = str_replace(',', '.', (string) $value);
        $search = ['{\\.+}si', '{[^-\\d.]}si'];
        $replace = ['.', ''];
        $value = preg_replace($search, $replace, $value);
        if (!strlen($value) || !self::isFloat($value)) {
            return null;
        }
        return floatval($value);
    }

    private static function isFloat($value) {
        // @TODO: ['+'|'-'] [digit* '.'] digit+ [('e'|'E') ['+'|'-'] digit+]
        return (bool) preg_match('{^[-+]?[0-9]+(?:\\.[0-9]*)?$}is', $value);
    }
}