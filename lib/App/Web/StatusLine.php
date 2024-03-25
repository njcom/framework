<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Stringable;

// https://www.rfc-editor.org/rfc/rfc9112.html#name-status-line
class StatusLine implements Stringable {
    public HttpVersion $httpVersion;
    public StatusCode $statusCode;

    public function __construct(HttpVersion $httpVersion = null, StatusCode $statusCode = null) {
        $this->httpVersion = $httpVersion ?: Env::httpVersion();
        $this->statusCode = $statusCode ?: StatusCode::Ok;
    }

    public function __toString(): string {
        return 'HTTP/' . $this->httpVersion->value . ' ' . $this->statusCode->value; // . ' ' . $this->statusCode->reason()
    }
}