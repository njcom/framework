<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

/**
 * Supported HTTP methods. See:
 *     [Method definitions in RFC 7231](https://datatracker.ietf.org/doc/html/rfc7231#section-4)
 *     [PATCH method in RFC 5789](https://tools.ietf.org/html/rfc5789)
 */
enum HttpMethod: string {
    case Get = 'GET';
    case Head = 'HEAD';
    case Post = 'POST';
    case Put = 'PUT';
    case Delete = 'DELETE';
    case Connect = 'CONNECT';
    case Options = 'OPTIONS';
    case Trace = 'TRACE';
    case Patch = 'PATCH';

    public static function isValid(string $httpMethod): bool {
        // @todo
        return true;
        dt();
        foreach (self::cases() as $case) {
            d($case);
        }
    }
}
