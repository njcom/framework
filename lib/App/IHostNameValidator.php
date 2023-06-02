<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use RuntimeException;

interface IHostNameValidator {
    /**
     * @throws RuntimeException
     */
    public function throwInvalidSiteError(): void;

    /**
     * @return string|false
     */
    public function currentHostName();

    public function isValid($hostName): bool;
}