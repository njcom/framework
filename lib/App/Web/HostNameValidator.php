<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Morpho\App\IHostNameValidator;
use RuntimeException;

use function explode;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;

class HostNameValidator implements IHostNameValidator {
    private array $allowedHostNames;

    public function __construct(array $allowedHostNames) {
        $this->allowedHostNames = $allowedHostNames;
    }

    /**
     * @return string|false
     */
    public function currentHostName() {
        // Use the `Host` header field-value, see https://tools.ietf.org/html/rfc3986#section-3.2.2
        $host = $_SERVER['HTTP_HOST'] ?? null;

        if (empty($host)) {
            return false;
            //throw new BadRequestException("Empty value of the 'Host' field");
        }

        // @TODO: Unicode and internationalized domains, see https://tools.ietf.org/html/rfc5892
        if (false !== ($startOff = strpos($host, '['))) {
            // IPv6 or later.
            if ($startOff !== 0) {
                return false;
//                throw new BadRequestException("Invalid value of the 'Host' field");
            }
            $endOff = strrpos($host, ']', 2);
            if (false === $endOff) {
                return false;
                //throw new BadRequestException("Invalid value of the 'Host' field");
            }
            $hostWithoutPort = strtolower(substr($host, 0, $endOff + 1));
        } else {
            // IPv4 or domain name
            $hostWithoutPort = explode(':', strtolower((string) $host), 2)[0];
            if (substr($hostWithoutPort, 0, 4) === 'www.' && strlen($hostWithoutPort) > 4) {
                $hostWithoutPort = substr($hostWithoutPort, 4);
            }
        }
        return $hostWithoutPort;
    }

    /**
     * @throws RuntimeException
     */
    public function throwInvalidSiteError(): void {
        throw new BadRequestException("Invalid host or site");
    }

    public function isValid($hostName): bool {
        return in_array($hostName, $this->allowedHostNames, true);
    }
}
