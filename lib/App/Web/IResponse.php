<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Morpho\Uri\Uri;
use Morpho\App\IResponse as IBaseResponse;

interface IResponse extends IBaseResponse {
    public function isRedirect(): bool;

    public function isSuccess(): bool;

    public function mkRedirect(string|Uri $uri, StatusCode $statusCode = null): static;

    public function mkStatusLine(StatusCode $statusCode): StatusLine;

    public function resetState(): void;
}