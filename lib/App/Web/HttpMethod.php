<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

/**
 * Supported HTTP methods. See:
 *     [Method definitions in RFC 7231](https://tools.ietf.org/html/rfc7231#section-4.3)
 *     [PATCH method in RFC 5789](https://tools.ietf.org/html/rfc5789)
 */
enum HttpMethod: string {
    case Get = 'GET';
    case Post = 'POST';
    case Delete = 'DELETE';
    case Patch = 'PATCH';
    case Put = 'PUT';
    case Head = 'HEAD';
    //public const Connect = 'CONNECT';
    //public const Options = 'OPTIONS';
    //public const Trace = 'TRACE';
}
