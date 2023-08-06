<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use ArrayObject;
use Morpho\App\Controller as BaseController;
use Morpho\Base\IHasServiceManager;
use Morpho\Base\IServiceManager;
use Morpho\Base\Result;
use Morpho\Uri\Uri;

abstract class Controller extends BaseController implements IHasServiceManager {
    private IRequest $request;

    protected IServiceManager $serviceManager;

    public function setServiceManager(IServiceManager $serviceManager): static {
        $this->serviceManager = $serviceManager;
        return $this;
    }

    protected function beforeEach($request): void {
        parent::beforeEach($request);
        $this->request = $request;
    }

    protected function request(): IRequest {
        return $this->request;
    }

    protected function redirect(string $uri = null, int $statusCode = null): IResponse {
        $request = $this->request;
        if (null === $uri) {
            $uri = $request->uri();
            $query = $uri->query();
            if (isset($query['redirect'])) {
                $redirectUri = rawurldecode($query['redirect']);
                $uri = new Uri($redirectUri);
                $query = $uri->query();
                if (isset($query['redirect'])) {
                    unset($query['redirect']);
                }
            }
        }
        return $request->response()->redirect($uri, $statusCode);
    }

    protected function jsConf(): ArrayObject {
        if (!isset($this->request['jsConf'])) {
            $this->request['jsConf'] = new ArrayObject();
        }
        return $this->request['jsConf'];
    }

    protected function messenger(): View\Messenger {
        return $this->serviceManager['messenger'];
    }

    protected function handleResult(mixed $actionResult): mixed {
        if ($actionResult instanceof Result) {
            $response = $this->request->response();
            $response->allowAjax(true)
                ->setFormats([ContentFormat::JSON]);
        }
        return $actionResult;
    }
}
