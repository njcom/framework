<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Cli;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\ErrorLogHandler as PhpErrorLogWriter;
use Monolog\Logger;
use Monolog\LogRecord;
use Morpho\App\ServiceManager as BaseServiceManager;
use Morpho\Base\EventManager;
use Morpho\Base\NotImplementedException;
use Morpho\Tech\Php\ErrorHandler;
use Morpho\Tech\Php\LogListener;
use Morpho\Tech\Php\NoDupsListener;

class ServiceManager extends BaseServiceManager {
    protected function mkAppInitializerService() {
        return new AppInitializer($this);
    }

    protected function mkErrorLoggerService() {
        $logger = new Logger('error');

        if (ErrorHandler::isErrorLogEnabled()) {
            $logger->pushHandler(new PhpErrorLogWriter());
        }

        $logger->pushHandler(
            new class extends AbstractProcessingHandler {
                protected function write(LogRecord $record): void {
                    errorLn($record['message']);
                }
            }
        );

        return $logger;
    }

    protected function mkErrorHandlerService() {
        $listeners = [];
        $logListener = new LogListener($this['errorLogger']);
        $listeners[] = $this->conf['errorHandler']['noDupsListener']
            ? new NoDupsListener($logListener)
            : $logListener;
        /*
        if ($this->conf['errorHandler']['dumpListener']) {
            $listeners[] = new DumpListener();
        }
        */
        return new ErrorHandler($listeners);
    }

    protected function mkRequestService() {
        return new Request();
    }

    protected function mkRouterService() {
        throw new NotImplementedException();
    }

    protected function mkEventManagerService() {
        $eventManager = new EventManager();
        $eventManager->on(
            'dispatchError',
            function ($event) {
                throw $event->args['exception'];
            }
        );
        return $eventManager;
    }
}
