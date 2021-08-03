<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use ArrayObject;
use Morpho\App\IResponse as IBaseResponse;
use Morpho\Uri\Uri;

interface IResponse extends IBaseResponse {
    public function allowAjax(bool $flag = null): bool|self;

    public function setFormats(array $formats): self;

    public function formats(): array;

    public function redirect(string|Uri $uri, int $statusCode = null): self;

    public function headers(): ArrayObject;

    public function isRedirect(): bool;

    public function setStatusLine(string $statusLine): void;

    public function resetState(): void;

    public function resetStatusCode(): void;

    public function isSuccess(): bool;

    public function send(): void;

    public function statusLine(): string;

    public function statusCodeToStatusLine(int $statusCode): string;

    public function statusCodeToReason(int $statusCode): string;
}