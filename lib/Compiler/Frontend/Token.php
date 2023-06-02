<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

class Token {
    /**
     * AKA Token-class
     */
    public readonly mixed $type;
    /**
     * Value of the token, lexeme.
     * @var string
     */
    public readonly string $val;
    public readonly Location $location;
    public array $meta = [];

    public function __construct($type, string $val, Location $location, array $meta = []) {
        $this->type = $type;
        $this->val = $val;
        $this->location = $location;
        $this->meta = $meta;
    }
}