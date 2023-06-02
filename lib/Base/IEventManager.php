<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Base;

interface IEventManager {
    public function on(string $eventName, callable $handler): void;

    public function off(string $eventName, callable $handlerSelector = null): void;

    /**
     * @param Event $event
     * @return mixed
     */
    public function trigger(Event $event);
}
