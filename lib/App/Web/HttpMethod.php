<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Morpho\Base\Enum;

/**
 * Supported HTTP methods. See:
 *     [Method definitions in RFC 7231](https://tools.ietf.org/html/rfc7231#section-4.3)
 *     [PATCH method in RFC 5789](https://tools.ietf.org/html/rfc5789)
 */
abstract class HttpMethod extends Enum {
    public const GET = 'GET';
    public const POST = 'POST';
    public const DELETE = 'DELETE';
    public const PATCH = 'PATCH';
    public const PUT = 'PUT';
    public const HEAD = 'HEAD';
    //public const CONNECT = 'CONNECT';
    //public const OPTIONS = 'OPTIONS';
    //public const TRACE = 'TRACE';
}
