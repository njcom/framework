<?php declare(strict_types=1);
namespace Morpho\Site\Localhost\App\Web;

use Morpho\App\Web\Controller;

/**
 * @noRoutes
 */
class ErrorController extends Controller {
    public function badRequest($request) {
        $request->response->statusCode = 400;
    }

    public function forbidden($request) {
        $request->response->statusCode = 403;
    }

    public function notFound($request) {
        $request->response->statusCode = 404;
    }

    public function uncaught($request) {
        $request->response->statusCode = 500;
    }

    public function methodNotAllowed($request) {
        $request->response->statusCOde = 405;
    }
}
