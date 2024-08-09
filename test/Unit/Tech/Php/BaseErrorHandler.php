<?php declare(strict_types=1);
/**
 * This file is part of njcom/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\Php;

use Morpho\Tech\Php\HandlerManager;
use Morpho\Testing\TestCase;

use function Morpho\Base\op;
use function restore_error_handler;
use function restore_exception_handler;
use function set_error_handler;
use function set_exception_handler;

abstract class BaseErrorHandler extends TestCase {
    protected $prevErrorHandler;
    protected $prevExceptionHandler;

    protected function setUp(): void {
        $handler = set_error_handler([$this, __FUNCTION__]);
        restore_error_handler();
        $this->prevErrorHandler = $handler;
        $handler = set_exception_handler([$this, __FUNCTION__]);
        restore_exception_handler();
        $this->prevExceptionHandler = $handler;
        unset($this->handlerArgs);
    }

    protected function tearDown(): void {
        // @todo: rewrite without HandlerManager, use only native PHP features
        HandlerManager::popHandlersUntil(HandlerManager::ERROR, op('===', $this->prevErrorHandler));
        HandlerManager::popHandlersUntil(HandlerManager::EXCEPTION, op('===', $this->prevExceptionHandler));
    }
}