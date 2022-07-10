<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler as PhpErrorLogWriter;
use Monolog\Handler\NativeMailerHandler as NativeMailerLogWriter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Morpho\App\ServiceManager as BaseServiceManager;
use Morpho\App\Web\View\FormProcessor;
use Morpho\App\Web\View\HtmlResponseRenderer;
use Morpho\App\Web\View\JsonResponseRenderer;
use Morpho\App\Web\View\Messenger;
use Morpho\App\Web\View\PhpProcessor;
use Morpho\App\Web\View\PhpTemplateEngine;
use Morpho\App\Web\View\RcProcessor;
use Morpho\App\Web\View\UriProcessor;
use Morpho\Base\IHasServiceManager;
use Morpho\Tech\Php\DumpListener;
use Morpho\Tech\Php\LogListener;
use Morpho\Tech\Php\NoDupsListener;
use UnexpectedValueException;

use function Morpho\Base\classify;
use function Morpho\Base\init;

class ServiceManager extends BaseServiceManager {
    protected function mkRouterService() {
        //return new Router($this['db']);
        return new FastRouter();
    }

    protected function mkRouteMetaProviderService() {
        return new RouteMetaProvider();
    }

    protected function mkAppInitializerService() {
        return new AppInitializer($this);
    }

    protected function mkSessionService() {
        return new Session(__CLASS__);
    }

    protected function mkRequestService() {
        return new Request();
    }

    protected function mkDebugLoggerService() {
        $logger = new Logger('debug');
        $this->appendLogFileWriter($logger, Logger::DEBUG);
        return $logger;
    }

    private function appendLogFileWriter(Logger $logger, int $logLevel): void {
        $moduleIndex = $this['backendModuleIndex'];
        $filePath = $moduleIndex->module($this['site']->moduleName())->logDirPath() . '/' . $logger->getName() . '.log';
        $handler = new StreamHandler($filePath, $logLevel);
        $handler->setFormatter(
            new LineFormatter(
                LineFormatter::SIMPLE_FORMAT . "-------------------------------------------------------------------------------\n",
                null,
                true
            )
        );
        $logger->pushHandler($handler);
    }

    protected function mkTemplateEngineService() {
        $conf = $this->conf['templateEngine'];
        $conf['pluginFactory'] = $this['templateEnginePluginFactory'];
        $conf['request'] = $this['request'];
        $conf['site'] = $this['site'];
        $conf['steps'] = [
            'phpProcessor'  => new PhpProcessor(),
            'uriProcessor'  => new UriProcessor($conf['request']),
            'formPersister' => new FormProcessor($conf['request']),
            'rcProcessor'   => new RcProcessor($conf['request'], $conf['site']),
        ];
        return new PhpTemplateEngine($conf);
    }

    protected function mkTemplateEnginePluginFactoryService() {
        if (isset($this->conf['templateEngine']['pluginFactory'])) {
            $factory = $this->conf['templateEngine']['pluginFactory'];
        } else {
            $factory = function ($pluginName) {
                $class = init(__NAMESPACE__, '\\') . '\\Web\\View\\' . classify($pluginName) . 'Plugin';
                $plugin = new $class();
                if ($plugin instanceof IHasServiceManager) {
                    $plugin->setServiceManager($this);
                }
                return $plugin;
            };
        }
        return $factory;
    }

    /*    protected function mkAutoloaderService() {
            return composerAutoloader();
        }*/

    protected function mkActionResultRendererService() {
        return new ActionResultRenderer(
            function ($format) {
                if ($format === ContentFormat::HTML) {
                    return new HtmlResponseRenderer(
                        $this['templateEngine'],
                        $this['backendModuleIndex'],
                        $this->conf()['view']['pageRenderingModule'],
                    );
                } elseif ($format === ContentFormat::JSON) {
                    return new JsonResponseRenderer();
                }
                // todo: add XML
                throw new UnexpectedValueException();
            }
        );
    }

    protected function mkMessengerService() {
        return new Messenger();
    }

    protected function mkEventManagerService() {
        return new EventManager($this);
    }

    protected function mkRouterCacheService() {
        return $this->mkCache($this->cacheDirPath() . '/router');
    }

    protected function mkErrorLoggerService() {
        $logger = (new Logger('error'))
            ->pushProcessor(new LogRecordProcessor())
            ->pushProcessor(new MemoryUsageProcessor())
            ->pushProcessor(new MemoryPeakUsageProcessor())
            ->pushProcessor(new IntrospectionProcessor());

        $conf = $this->conf['errorLogger'];

        if ($conf['errorLogWriter'] && ErrorHandler::isErrorLogEnabled()) {
            $logger->pushHandler(new PhpErrorLogWriter());
        }

        if (!empty($conf['mailWriter']['enabled'])) {
            $logger->pushHandler(
                new NativeMailerLogWriter($conf['mailTo'], 'An error has occurred', $conf['mailFrom'], Logger::NOTICE)
            );
        }

        if ($conf['logFileWriter']) {
            $this->appendLogFileWriter($logger, Logger::DEBUG);
        }

        /*       if ($conf['debugWriter']) {
                   $logger->pushHandler(new class extends \Monolog\Handler\AbstractProcessingHandler {
                       protected function write(array $record): void {
                           d($record['message']);
                       }
                   });
               }*/

        return $logger;
    }

    protected function mkDispatchErrorHandlerService() {
        $dispatchErrorHandler = new DispatchErrorHandler();
        $conf = $this->conf()['dispatchErrorHandler'];
        $dispatchErrorHandler->throwErrors($conf['throwErrors']);
        $dispatchErrorHandler->setExceptionHandler($conf['exceptionHandler']);
        return $dispatchErrorHandler;
    }

    protected function mkErrorHandlerService() {
        $listeners = [];
        $logListener = new LogListener($this['errorLogger']);
        $listeners[] = $this->conf['errorHandler']['noDupsListener']
            ? new NoDupsListener($logListener)
            : $logListener;
        if ($this->conf['errorHandler']['dumpListener']) {
            $listeners[] = new DumpListener();
        }
        return new ErrorHandler($listeners);
    }
}
