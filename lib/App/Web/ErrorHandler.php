<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Closure;
use Morpho\Tech\Php\ErrorHandler as BaseErrorHandler;
use Throwable;

use function header;
use function headers_sent;
use function Morpho\Base\e;
use function ob_end_clean;
use function ob_get_level;

class ErrorHandler extends BaseErrorHandler {
    public function __construct(iterable $listeners = null) {
        parent::__construct($listeners);
        $this->listeners()->append($this->mkListener());
    }

    protected function mkListener(): Closure {
        return function (Throwable $e) {
            $statusLine = null;
            /*            if ($e instanceof NotFoundException) {
                              $statusLine = Environment::httpProto() . ' 404 Not Found';
                              $message = "The requested resource was not found";
                          } elseif ($e instanceof AccessDeniedException) {
                              $statusLine = Environment::httpProto() . ' 403 Forbidden';
                              $message = "You don't have access to the requested resource";
                          } else*/
            if ($e instanceof BadRequestException) {
                $statusLine = Env::httpProto() . ' 400 Bad Request';
                $message = "Bad request, please contact site's support";
            } else {
                $statusLine = Env::httpProto() . ' 500 Internal Server Error';
                $message = "Unable to handle the request. Please contact site's support and try to return to this page again later";
            }
            if (!headers_sent()) {
                // @TODO: Use http_response_code()?
                header($statusLine);
            }
            for ($i = 0, $n = ob_get_level(); $i < $n; $i++) {
                //ob_end_flush();
                ob_end_clean();
            }
            echo e($message) . '.';
        };
    }
}