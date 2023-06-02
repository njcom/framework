<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Base;

class Ok extends Result {
    public function __construct(mixed $val = null) {
        parent::__construct($val);
    }

    public function isOk(): bool {
        return true;
    }

    public function jsonSerialize(): array {
        return ['ok' => $this->val];
    }
}
