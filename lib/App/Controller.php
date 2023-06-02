<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use Morpho\Base\IFn;

abstract class Controller implements IFn {
    public function __invoke(mixed $request): mixed {
        $this->beforeEach($request);
        $this->runAction($request);
        $this->afterEach($request);
        return $request;
    }

    /**
     * Called before calling of any action.
     */
    protected function beforeEach($request): void {
    }

    /**
     * Called after calling of any action.
     */
    protected function afterEach($request): void {
    }

    protected function runAction($request): void {
        $handler = $request->handler();
        $methodName = $handler['method'];
        // @todo: ensure that is is safe to pass ...$args
        //$args = $handler['args'];
        $actionResult = $this->$methodName(/*...array_values($args)*/);
        $result = $this->handleResult($actionResult);
        if (!$result instanceof IResponse) {
            $response = $this->request()->response();
            $response['result'] = $result;
        }
    }

    protected function handleResult(mixed $actionResult): mixed {
        return $actionResult;
    }

    abstract protected function request(): IRequest;
}
