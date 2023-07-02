<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Cli;

use Morpho\App\IHostNameValidator;

class HostNameValidator implements IHostNameValidator {
    private array $allowedHostNames;
    private string $currentHostName;

    public function __construct(array $allowedHostNames, string $currentHostName) {
        $this->allowedHostNames = $allowedHostNames;
        $this->currentHostName = $currentHostName;
    }

    public function throwInvalidSiteError(): never {
        throw new Exception('Invalid site');
    }

    public function currentHostName(): string|false {
        return $this->currentHostName;
    }

    public function isValid($hostName): bool {
        return in_array($hostName, $this->allowedHostNames, true);
    }
}
