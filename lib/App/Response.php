<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

abstract class Response extends Message implements IResponse {
    protected string $body = '';

    protected int $statusCode = 0;

    /**
     * @param string
     */
    public function setBody(string $body): void {
        $this->body = $body;
    }

    public function body(): string {
        return $this->body;
    }

    public function isBodyEmpty(): bool {
        return !isset($this->body[0]);
    }

    public function send(): void {
        $this->sendBody();
    }

    protected function sendBody(): void {
        echo $this->body;
    }

    public function setStatusCode(int $statusCode): void {
        $this->statusCode = $statusCode;
    }

    public function statusCode(): int {
        return $this->statusCode;
    }

    public function resetState(): void {
        $this->exchangeArray([]);
        $this->resetStatusCode();
        $this->resetBody();
    }

    public function resetStatusCode(): void {
        $this->statusCode = 0;
    }

    public function resetBody(): void {
        $this->body = '';
    }
}
