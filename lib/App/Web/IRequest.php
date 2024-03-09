<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use ArrayObject;
use Morpho\App\IMessage;
use Morpho\Uri\Uri;

interface IRequest extends IMessage {
    public function redirect(string $uri = null, int $statusCode = null): IResponse;

    public function setServerVars(array $vars): void;

    public function setServerVar(string $name, mixed $val): void;

    public function serverVar(string $name, $default = null): mixed;

    //public function setResponse(IResponse $response): void;

    //public function response(): IResponse;

    public function isKnownMethod(string $method): bool;

    public function setMethod(HttpMethod $method): void;

    public function method(): HttpMethod;

    public function isAjax(bool $flag = null): bool;

    public function headers(): ArrayObject;

    public function setUri(Uri $uri): void;

    public function uri(): Uri;

    public function prependWithBasePath(string $path): Uri;

    public function setTrustedProxyIps(array $ips): void;

    public function trustedProxyIps(): ?array;
}