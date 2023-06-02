<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use ArrayObject;
use Morpho\App\IRequest as IBaseRequest;
use Morpho\Uri\Uri;

interface IRequest extends IBaseRequest {
    public function setServerVars(array $vars): void;

    public function setServerVar(string $name, mixed $val): void;

    public function serverVar(string $name, $default = null): mixed;

    public function setResponse(IResponse $response): void;

    public function response(): IResponse;

    public function setMethod(HttpMethod $method): void;

    public function method(): HttpMethod;

    public function isGetMethod(): bool;

    public function isPostMethod(): bool;

    public function isDeleteMethod(): bool;

    public function isPatchMethod(): bool;

    public function isPutMethod(): bool;

    public function isHeadMethod(): bool;

    public function knownMethods(): array;

    public function isKnownMethod($method): bool;

    public function args(string|array|null $names = null, callable|bool $filter = true): mixed;

    public function query($name = null, callable|bool $filter = true): mixed;

    public function hasQuery(string $name): bool;

    public function post($name = null, callable|bool $filter = true): mixed;

    public function hasPost(string $name): bool;

    public function patch($name = null, callable|bool $filter = true): mixed;

    public function data(array $source, $name = null, callable|bool $filter = true): mixed;

    public function isAjax(bool $flag = null): bool;

    public function headers(): ArrayObject;

    public function setUri(Uri $uri): void;

    public function uri(): Uri;

    public function prependWithBasePath(string $path): Uri;

    public function setTrustedProxyIps(array $ips): void;

    public function trustedProxyIps(): ?array;
}