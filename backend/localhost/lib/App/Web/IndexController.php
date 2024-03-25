<?php declare(strict_types=1);
namespace Morpho\Site\Localhost\App\Web;

use Morpho\App\Web\Controller;

class IndexController extends Controller {
    /**
     * @GET /
     */
    public function index() {
        dd();
    }
}
