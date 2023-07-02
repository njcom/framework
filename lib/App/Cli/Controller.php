<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Cli;

use Morpho\App\Controller as BaseController;

class Controller extends BaseController {
    private IRequest $request;

    protected function beforeEach($request): void {
        parent::beforeEach($request);
        $this->request = $request;
    }

    protected function handleResult(mixed $actionResult): IResponse {
        $response = $this->request()->response();
        $actionResult = (string) $actionResult;
        $response['result'] = $actionResult;
        return $response;
    }

    protected function request(): IRequest {
        return $this->request;
    }
}
