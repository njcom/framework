<?php declare(strict_types=1);
namespace Morpho\Site\Localhost\App\Web;

use Morpho\App\Web\Controller;
use Morpho\App\Web\StatusCode;

/**
 * @noRoutes
 */
class ErrorController extends Controller {
    public function badRequest($request) {
        $request->response->statusLine->statusCode = StatusCode::BadRequest;
    }

    public function forbidden($request) {
        $request->response->statusLine->statusCode = StatusCode::Forbidden;
    }

    public function notFound($request) {
        $request->response->statusLine->statusCode = StatusCode::NotFound;
    }

    public function uncaught($request) {
        $request->response->statusLine->statusCode = StatusCode::InternalServerError;
    }

    public function methodNotAllowed($request) {
        $request->response->statusLine->statusCode = StatusCode::MethodNotAllowed;
    }
}
