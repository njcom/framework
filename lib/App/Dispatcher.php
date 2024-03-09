<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use Morpho\Base\IFn;
use RuntimeException;
use Throwable;

class Dispatcher implements IFn {
    public int $numOfIterations = 20;

    private IFn $handlerProvider;
    private IFn $resultRenderer;
    private IFn $dispatchErrorHandler;

    public function __construct(IFn $handlerProvider, IFn $resultRenderer, IFn $dispatchErrorHandler) {
        $this->handlerProvider = $handlerProvider;
        $this->resultRenderer = $resultRenderer;
        $this->dispatchErrorHandler = $dispatchErrorHandler;
    }

    public function __invoke(mixed $request): mixed {
        $i = 0;
        do {
            $request->handled = false;

            if ($i >= $this->numOfIterations) {
                throw new RuntimeException("Dispatch loop has been detected, iterated {$this->numOfIterations} times");
            }
            try {
                // $this->eventManager->trigger(new Event('beforeDispatch', ['request' => $request]));
                $request = $this->beforeDispatch($request);

                $fn = ($this->handlerProvider)($request);
                $request->handler['fn'] = $fn;
                $request->response['result'] = $fn($request);

                $request->handled = true;

                $request = $this->afterDispatch($request);
                //$this->eventManager->trigger(new Event('afterDispatch', ['request' => $request]));
            } catch (Throwable $e) {
                $request['error'] = $e;
                $request = $this->dispatchError($request);
                //$this->eventManager->trigger(new Event('dispatchError', ['request' => $request, 'exception' => $e]));
            }
            $i++;
        } while (!$request->handled);

        return $request;
    }

    protected function beforeDispatch(mixed $request): mixed {
        return $request;
    }

    protected function afterDispatch(mixed $request): mixed {
        return $this->resultRenderer->__invoke($request);
    }

    protected function dispatchError(mixed $request): mixed {
        return $this->dispatchErrorHandler->__invoke($request);
    }
}
