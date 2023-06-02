<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use InvalidArgumentException;
use RuntimeException;

use function array_reverse;
use function call_user_func;
use function in_array;
use function set_error_handler;
use function set_exception_handler;

/**
 * Utility class to manage error and exception handlers.
 */
class HandlerManager {
    public const ERROR = 'error';
    public const EXCEPTION = 'exception';

    public static function isHandlerRegistered(string $handlerType, callable $callback): bool {
        return in_array($callback, self::handlersOfType($handlerType));
    }

    public static function handlersOfType(string $handlerType): array {
        self::checkHandlerType($handlerType);

        $popHandler = 'restore_' . $handlerType . '_handler';
        $pushHandler = 'set_' . $handlerType . '_handler';

        $handlers = [];
        do {
            $handler = self::handlerOfType($handlerType);
            $popHandler();
            if (!$handler) {
                break;
            }
            $handlers[] = $handler;
        } while (true);

        $handlers = array_reverse($handlers);

        // Restore handlers back.
        foreach ($handlers as $handler) {
            $pushHandler($handler);
        }

        return $handlers;
    }

    public static function handlerOfType(string $handlerType): ?callable {
        self::checkHandlerType($handlerType);

        $currentHandler = call_user_func('set_' . $handlerType . '_handler', [__CLASS__, __FUNCTION__]);
        call_user_func('restore_' . $handlerType . '_handler');

        return $currentHandler;
    }

    public static function registerHandler(string $handlerType, callable $callback): ?callable {
        if ($handlerType === self::ERROR) {
            return set_error_handler($callback);
        } elseif ($handlerType === self::EXCEPTION) {
            return set_exception_handler($callback);
        }
        self::invalidHandlerTypeException($handlerType);
    }

    /**
     * @param string $handlerType
     * @param callable|null $fn
     *     If null all handlers will be deleted. If callback was provided then all handlers before will be deleted that are above in the inner PHP stack of handlers.
     */
    public static function unregisterHandler(string $handlerType, callable $fn = null): void {
        self::checkHandlerType($handlerType);
        if (null === $fn) {
            // Restore default error handler
            ('set_' . $handlerType . '_handler')(null);
        } else {
            $popHandler = 'restore_' . $handlerType . '_handler';
            $handlers = [];
            $found = false;
            /** @noinspection PhpAssignmentInConditionInspection */
            while ($handler = self::handlerOfType($handlerType)) {
                $popHandler();
                if ($handler === $fn) {
                    $found = true;
                    break;
                } else {
                    $handlers[] = $handler;
                }
            }
            $pushHandler = 'set_' . $handlerType . '_handler';
            foreach (array_reverse($handlers) as $handler) {
                $pushHandler($handler);
            }
            if (!$found) {
                throw new RuntimeException('Unable to unregister the ' . $handlerType . ' handler');
            }
        }
    }

    public static function popHandlersUntil(string $handlerType, callable $predicate): void {
        self::checkHandlerType($handlerType);
        $popHandler = 'restore_' . $handlerType . '_handler';
        /** @noinspection PhpAssignmentInConditionInspection */
        while ($currentHandler = self::handlerOfType($handlerType)) {
            if ($predicate($currentHandler)) {
                return;
            }
            $popHandler();
        }
    }

    public static function exceptionHandler(): ?callable {
        return self::handlerOfType(self::EXCEPTION);
    }

    public static function errorHandler(): ?callable {
        return self::handlerOfType(self::ERROR);
    }

    public static function exceptionHandlers(): array {
        return self::handlersOfType(self::EXCEPTION);
    }

    public static function errorHandlers(): array {
        return self::handlersOfType(self::ERROR);
    }

    private static function checkHandlerType(string $handlerType): void {
        if (!in_array($handlerType, [self::ERROR, self::EXCEPTION], true)) {
            self::invalidHandlerTypeException($handlerType);
        }
    }

    private static function invalidHandlerTypeException(string $handlerType)/* todo: never */ {
        throw new InvalidArgumentException("Invalid handler type was provided '$handlerType'.");
    }
}
