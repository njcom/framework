<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

interface IResponse extends IMessage {
    public function setBody(string $body): void;

    public function body(): string;

    public function isBodyEmpty(): bool;

    public function send(): void;

    public function setStatusCode(int $statusCode): void;

    public function statusCode(): int;

    public function resetState(): void;
}
