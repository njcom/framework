<?php declare(strict_types=1);
namespace Morpho\Site\Localhost\App\Web;

use Morpho\App\Web\Controller;

class TestController extends Controller {
    /**
     * @GET /test
     */
    public function index() {
        //$this->setParentViewResult('test/test');
    }

    public function testStatus400() {
        return $this->mkBadRequestResult();
    }

    public function testStatus403() {
        return $this->mkForbiddenResult();
    }

    public function testStatus404() {
        return $this->mkNotFoundResult();
    }

    public function testStatus405() {
        // For testing clients should send: POST $prefix/test/status405
    }

    public function testStatus500() {
        throw new \RuntimeException();
    }

    /**
     * @POST
     */
    public function testRedirectTest() {
        return $this->mkJsonResult([
            'ok' => [
                'redirect' => '/go/to/linux',
            ]
        ]);
    }

    /**
     * @POST
     */
    public function testError() {
        return $this->mkJsonResult([
            'err' => [
                [
                   "text" => 'This is a<br>multiple line error with the {0} and {1} arguments.',
                    "args" => [
                        "Should<br>not be escaped",
                        "Contains '"
                    ]
               ],
            ]
        ]);
    }
}
