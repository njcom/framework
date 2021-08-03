<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use ArrayObject;
use Morpho\App\Response as BaseResponse;
use RuntimeException;
use Morpho\Uri\Uri;

use function header;
use function intval;
use function is_string;

class Response extends BaseResponse implements IResponse {
    public const OK_STATUS_CODE = 200;
    public const MOVED_PERMANENTLY = 301;
    public const FOUND_STATUS_CODE = 302;
    public const NOT_MODIFIED_STATUS_CODE = 304;
    public const BAD_REQUEST_STATUS_CODE = 400;

    public const FORBIDDEN_STATUS_CODE = 403;
    public const NOT_FOUND_STATUS_CODE = 404;
    public const METHOD_NOT_ALLOWED = 405;
    public const INTERNAL_SERVER_ERROR_STATUS_CODE = 500;
    public const SERVICE_UNAVAILABLE_CODE = 503;
    protected int $statusCode = self::OK_STATUS_CODE;
    protected ?ArrayObject $headers;
    private ?string $statusLine = null;
    private array $formats;
    private bool $allowAjax = false;

    public function __construct(array $input = null) {
        parent::__construct((array) $input);
        $this->headers = new ArrayObject();
        $this->formats = [
            ContentFormat::HTML,
            // ContentFormat::JSON,
            // ContentFormat::XML,
            // ContentFormat::TEXT => false,
            // ContentFormat::BIN => false,
        ];
    }

    public function allowAjax(bool $flag = null): bool|self {
        if ($flag !== null) {
            $this->allowAjax = $flag;
            return $this;
        }
        return $this->allowAjax;
    }

    public function setFormats(array $formats): self {
        $this->formats = $formats;
        return $this;
    }

    public function formats(): array {
        return $this->formats;
    }

    public function redirect(string|Uri $uri, int $statusCode = null): self {
        $this->headers()->offsetSet('Location', is_string($uri) ? $uri : $uri->toStr(null, true));
        $this->setStatusCode($statusCode ?: self::FOUND_STATUS_CODE);
        return $this;
    }

    public function headers(): ArrayObject {
        return $this->headers;
    }

    public function isRedirect(): bool {
        $statusCode = $this->statusCode;
        return isset($this->headers()['Location'])
            && 300 <= $statusCode && $statusCode < 400;
    }

    public function setStatusLine(string $statusLine): void {
        $this->statusLine = $statusLine;
    }

    public function resetState(): void {
        parent::resetState();
        $this->headers->exchangeArray([]);
        $this->statusLine = '';
    }

    public function resetStatusCode(): void {
        $this->statusCode = self::OK_STATUS_CODE;
    }

    public function isSuccess(): bool {
        $statusCode = $this->statusCode;
        return 200 <= $statusCode && $statusCode < 400;
    }

    public function send(): void {
        $this->sendStatusLine();
        $this->sendHeaders();
        parent::send();
    }

    public function statusLine(): string {
        if (null == $this->statusLine) {
            $this->statusLine = $this->statusCodeToStatusLine($this->statusCode);
        }
        return $this->statusLine;
    }

    public function statusCodeToStatusLine(int $statusCode): string {
        return Env::httpProto() . ' ' . intval($statusCode) . ' ' . $this->statusCodeToReason($statusCode);
    }

    public function statusCodeToReason(int $statusCode): string {
        // http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
        $codeToReason = [
            self::OK_STATUS_CODE                    => 'OK',
            self::MOVED_PERMANENTLY                 => 'Moved Permanently',
            self::FOUND_STATUS_CODE                 => 'Found',
            self::NOT_MODIFIED_STATUS_CODE          => 'Not Modified',
            self::BAD_REQUEST_STATUS_CODE           => 'Bad Request',
            self::FORBIDDEN_STATUS_CODE             => 'Forbidden',
            self::NOT_FOUND_STATUS_CODE             => 'Not Found',
            self::METHOD_NOT_ALLOWED                => 'Method Not Allowed',
            self::INTERNAL_SERVER_ERROR_STATUS_CODE => 'Internal Server Error',
            self::SERVICE_UNAVAILABLE_CODE          => 'Service Unavailable',
        ];
        if (isset($codeToReason[$statusCode])) {
            return $codeToReason[$statusCode];
        }
        $codeToReason = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            103 => 'Early Hints',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            208 => 'Already Reported',
            226 => 'IM Used',
            300 => 'Multiple Choices',
            303 => 'See Other',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Payload Too Large',
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Range Not Satisfiable',
            417 => 'Expectation Failed',
            421 => 'Misdirected Request',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            425 => 'Unassigned',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            451 => 'Unavailable For Legal Reasons',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            510 => 'Not Extended',
            511 => 'Network Authentication Required',
        ];
        if (isset($codeToReason[$statusCode])) {
            return $codeToReason[$statusCode];
        }
        if ($statusCode === 509 || $statusCode === 430 || $statusCode === 427 || (104 <= $statusCode && $statusCode <= 199) || (209 <= $statusCode && $statusCode <= 225) || (227 <= $statusCode && $statusCode <= 299) || (309 <= $statusCode && $statusCode <= 399) || (418 <= $statusCode && $statusCode <= 420) || (432 <= $statusCode && $statusCode <= 450) || (452 <= $statusCode && $statusCode <= 499) || (512 <= $statusCode && $statusCode <= 599)) {
            return 'Unassigned';
        }
        throw new RuntimeException("Unable to map the status code to the reason phrase");
    }

    protected function sendHeaders(): void {
        foreach ($this->headers() as $name => $value) {
            $this->sendHeader($name . ': ' . $value);
        }
    }

    protected function sendStatusLine(): void {
        // @TODO: http_response_code
        $this->sendHeader($this->statusLine());
    }

    protected function sendHeader(string $value): void {
        header($value);
    }
}
