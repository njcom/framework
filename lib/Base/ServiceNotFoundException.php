<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Base;

use Exception;

class ServiceNotFoundException extends Exception {
    public function __construct(string $id) {
        parent::__construct("The service with ID '{$id}' was not found");
    }
}