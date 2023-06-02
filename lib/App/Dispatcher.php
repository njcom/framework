<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use Morpho\Base\Event;
use Morpho\Base\IEventManager;
use RuntimeException;
use Throwable;

class Dispatcher {
    protected int $maxNoOfDispatchIterations = 20;

    private IEventManager $eventManager;

    private $handlerProvider;

    public function __construct(callable $handlerProvider, IEventManager $eventManager) {
        $this->handlerProvider = $handlerProvider;
        $this->eventManager = $eventManager;
    }

    public function dispatch(IRequest $request): void {
        $i = 0;
        do {
            $request->isHandled(false);

            if ($i >= $this->maxNoOfDispatchIterations) {
                throw new RuntimeException(
                    "Dispatch loop has occurred, iterated {$this->maxNoOfDispatchIterations} times"
                );
            }
            try {
                $this->eventManager->trigger(new Event('beforeDispatch', ['request' => $request]));

                $handler = ($this->handlerProvider)($request);
                $handler($request);
                $request->isHandled(true);

                $this->eventManager->trigger(new Event('afterDispatch', ['request' => $request]));
            } catch (Throwable $e) {
                $this->eventManager->trigger(new Event('dispatchError', ['request' => $request, 'exception' => $e]));
            }
            $i++;
        } while (!$request->isHandled());
    }

    public function setMaxNoOfDispatchIterations(int $n): void {
        $this->maxNoOfDispatchIterations = $n;
    }

    public function maxNoOfDispatchIterations(): int {
        return $this->maxNoOfDispatchIterations;
    }
}
