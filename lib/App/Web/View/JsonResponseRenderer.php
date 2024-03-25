<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use function Morpho\Base\toJson;

class JsonResponseRenderer {
    public function __invoke(mixed $request): mixed {
        $response = $request->response;
        // https://tools.ietf.org/html/rfc7231#section-3.1.1
        $response->headers['Content-Type'] = 'application/json;charset=utf-8';
        $response->body = toJson($response['result']);
        return $request;
    }
}
