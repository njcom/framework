<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use UnexpectedValueException;

// https://datatracker.ietf.org/doc/html/rfc9110#name-status-codes
// https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
enum StatusCode: int {
    case Continue = 100;
    case SwitchingProtocols = 101;
    case Processing = 102;
    case EarlyHints = 103;
    case Ok = 200;
    case Created = 201;
    case Accepted = 202;
    case NonAuthoritativeInformation = 203;
    case NoContent = 204;
    case ResetContent = 205;
    case PartialContent = 206;
    case MultiStatus = 207;
    case AlreadyReported = 208;
    case IMUsed = 226;
    case MultipleChoices = 300;
    case MovedPermanently = 301;
    case Found = 302;
    case SeeOther = 303;
    case NotModified = 304;
    case UseProxy = 305;
    //case (Unused) = 306;
    case TemporaryRedirect = 307;
    case PermanentRedirect = 308;
    case BadRequest = 400;
    case Unauthorized = 401;
    case PaymentRequired = 402;
    case Forbidden = 403;
    case NotFound = 404;
    case MethodNotAllowed = 405;
    case NotAcceptable = 406;
    case ProxyAuthenticationRequired = 407;
    case RequestTimeout = 408;
    case Conflict = 409;
    case Gone = 410;
    case LengthRequired = 411;
    case PreconditionFailed = 412;
    case PayloadTooLarge = 413;
    case URITooLong = 414;
    case UnsupportedMediaType = 415;
    case RangeNotSatisfiable = 416;
    case ExpectationFailed = 417;
    case MisdirectedRequest = 421;
    case UnprocessableEntity = 422;
    case Locked = 423;
    case FailedDependency = 424;
    case Unassigned = 425;
    case UpgradeRequired = 426;
    case PreconditionRequired = 428;
    case TooManyRequests = 429;
    case RequestHeaderFieldsTooLarge = 431;
    case UnavailableForLegalReasons = 451;
    case InternalServerError = 500;
    case NotImplemented = 501;
    case BadGateway = 502;
    case ServiceUnavailable = 503;
    case GatewayTimeout = 504;
    case HTTPVersionNotSupported = 505;
    case VariantAlsoNegotiates = 506;
    case InsufficientStorage = 507;
    case LoopDetected = 508;
    case NotExtended = 510;
    case NetworkAuthenticationRequired = 511;

    public function reason(): string {
        $statusCode = $this->value;
        static $codeToReason;
        if (null === $codeToReason) {
            $codeToReason = [
                200 => 'OK',
                301 => 'Moved Permanently',
                302 => 'Found',
                304 => 'Not Modified',
                400 => 'Bad Request',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                500 => 'Internal Server Error',
                503 => 'Service Unavailable',
            ];
        }
        if (isset($codeToReason[$statusCode])) {
            return $codeToReason[$statusCode];
        }
        $codeToReason += [
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
        throw new UnexpectedValueException("Unable to map the status code to the reason phrase");
    }
}