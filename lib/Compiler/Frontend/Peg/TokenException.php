<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

// TokenError
use Morpho\Compiler\Frontend\Location;

class TokenException extends PegException {
    public function __construct(string $msg, Location $location) {
        // @todo: handle $args
        parent::__construct($msg . ', (' . $location->lineNo . ', ' . $location->columnNo . ')');
    }
}