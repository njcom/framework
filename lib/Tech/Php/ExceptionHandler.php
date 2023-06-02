<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use ArrayObject;
use LogicException;
use Throwable;

class ExceptionHandler implements IExceptionHandler {
    protected ArrayObject $listeners;
    private bool $registered = false;

    public function __construct(iterable $listeners = null) {
        if (null === $listeners) {
            $listeners = [];
        }
        $listeners1 = new ArrayObject();
        foreach ($listeners as $listener) {
            $listeners1->append($listener);
        }
        $this->listeners = $listeners1;
    }

    public function register(): void {
        if ($this->registered) {
            throw new LogicException();
        }
        HandlerManager::registerHandler(HandlerManager::EXCEPTION, [$this, 'handleException']);
        $this->registered = true;
    }

    public function unregister(): void {
        if (!$this->registered) {
            throw new LogicException();
        }
        HandlerManager::unregisterHandler(HandlerManager::EXCEPTION, [$this, 'handleException']);
    }

    public function handleException(Throwable $e): void {
        foreach ($this->listeners as $listener) {
            $listener($e);
        }
    }

    public function listeners(): ArrayObject {
        return $this->listeners;
    }
}
