<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use ArrayObject;
use Morpho\Uri\Uri;

use function header;
use function is_string;

class Response extends ArrayObject implements IResponse {
    public array $allowedFormats = [ContentFormat::HTML];
    public string $body = '';
    public ArrayObject $headers;
    //public bool $allowAjax = false;
    public StatusLine $statusLine;

    public function __construct(array $vals = null) {
        parent::__construct((array) $vals);
        $this->headers = new ArrayObject();
        $this->statusLine = new StatusLine();
    }

    public function isSuccess(): bool {
        $statusCode = $this->statusLine->statusCode->value;
        return 200 <= $statusCode && $statusCode < 400;
    }
    public function isRedirect(): bool {
        $statusCode = $this->statusLine->statusCode->value;
        return isset($this->headers['Location']) && 300 <= $statusCode && $statusCode < 400;
    }

    public function mkRedirect(string|Uri $uri, StatusCode $statusCode = null): static {
        $response = new static();
        $response->headers->offsetSet('Location', is_string($uri) ? $uri : $uri->toStr(null, true));
        $response->statusLine = $this->mkStatusLine(null !== $statusCode ? $statusCode : StatusCode::Found);
        return $response;
    }

    public function mkStatusLine(StatusCode $statusCode): StatusLine {
        return new StatusLine($this->statusLine->httpVersion, $statusCode);
    }

    public function resetState(): void {
        $this->headers->exchangeArray([]);
        $this->statusLine = new StatusLine();
        $this->body = '';
    }

    public function send(): mixed {
        $this->sendStatusLine();
        $this->sendHeaders();
        echo $this->body;
        return null;
    }

    protected function sendHeaders(): void {
        foreach ($this->headers as $name => $value) {
            $this->sendHeader($name . ': ' . $value);
        }
    }

    protected function sendStatusLine(): void {
        // @TODO: http_response_code
        $this->sendHeader($this->statusLine->__toString());
    }

    protected function sendHeader(string $header): void {
        header($header);
    }
}
