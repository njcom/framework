<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use Morpho\Base\Env;
use RuntimeException;
use Throwable;

use function error_clear_last;
use function error_get_last;
use function error_reporting;
use function in_array;
use function ini_get;
use function ini_set;
use function md5;
use function register_shutdown_function;
use function str_replace;

/**
 * ErrorHandler is main error/exception handler. It transforms errors to exceptions
 * and sends notification about exception to the attached subscribers.
 * Based on code and ideas found at:
 * @link https://github.com/DmitryKoterov/php_exceptionizer
 * @link https://github.com/DmitryKoterov/debug_errorhook
 */
class ErrorHandler extends ExceptionHandler implements IErrorHandler {
    private bool $exitOnFatalError = true;

    private bool $registerAsFatalErrorHandler = true;

    private bool $fatalErrorHandlerActive = false;

    private ?array $oldIniSettings = null;

    public static function checkError(bool $pred, string $msg = null): void {
        if (!$pred) {
            $error = error_get_last();
            if ($error) {
                error_clear_last();
                throw self::errorToException($error['type'], $error['message'], $error['file'], $error['line']);
            } else {
                throw new RuntimeException($msg);
            }
        }
    }

    public static function errorToException($severity, $message, $filePath, $lineNo): \ErrorException {
        $class = self::exceptionClass($severity);
        return new $class($message, 0, $severity, $filePath, $lineNo);
    }

    protected static function exceptionClass($severity): string {
        $levels = [
            E_ERROR             => 'ErrorException',
            E_WARNING           => 'WarningException',
            E_PARSE             => 'ParseException',
            E_NOTICE            => 'NoticeException',
            E_CORE_ERROR        => 'CoreErrorException',
            E_CORE_WARNING      => 'CoreWarningException',
            E_COMPILE_ERROR     => 'CompileErrorException',
            E_COMPILE_WARNING   => 'CompileWarningException',
            E_USER_ERROR        => 'UserErrorException',
            E_USER_WARNING      => 'UserWarningException',
            E_USER_NOTICE       => 'UserNoticeException',
            E_STRICT            => 'StrictException',
            E_RECOVERABLE_ERROR => 'RecoverableErrorException',
            E_DEPRECATED        => 'DeprecatedException',
            E_USER_DEPRECATED   => 'UserDeprecatedException',
        ];
        return __NAMESPACE__ . '\\' . $levels[$severity];
    }

    public static function trackErrors(callable $fn): mixed {
        $handler = function ($severity, $message, $filePath, $lineNo) {
            if (!(error_reporting() & $severity)) {
                return;
            }
            throw new \ErrorException($message, 0, $severity, $filePath, $lineNo);
        };
        HandlerManager::registerHandler(HandlerManager::ERROR, $handler);
        $res = $fn();
        HandlerManager::unregisterHandler(HandlerManager::ERROR, $handler);
        return $res;
    }

    public static function isErrorLogEnabled(): bool {
        return Env::boolIniVal('log_errors') && !empty(ini_get('error_log'));
    }

    public static function hashId(Throwable $e): string {
        return md5(str_replace("\x00", '', $e->getFile()) . "\x00" . $e->getLine());
    }

    public function register(): void {
        parent::register();

        HandlerManager::registerHandler(HandlerManager::ERROR, [$this, 'handleError']);

        if ($this->registerAsFatalErrorHandler) {
            register_shutdown_function([$this, 'handleFatalError']);
            $this->fatalErrorHandlerActive = true;
        }

        $this->setNewIniSettings();
    }

    protected function setNewIniSettings(): void {
        $oldIniSettings = [];
        $oldIniSettings['display_errors'] = ini_set('display_errors', '0');
        // @TODO: Do we need set the 'display_startup_errors'?
        $oldIniSettings['display_startup_errors'] = ini_set('display_startup_errors', '0');
        $this->oldIniSettings = $oldIniSettings;
    }

    public function unregister(): void {
        parent::unregister();

        HandlerManager::unregisterHandler(HandlerManager::ERROR, [$this, 'handleError']);

        // There is no unregister_shutdown_function(), so we emulate it via flag.
        $this->fatalErrorHandlerActive = false;

        $this->restorePreviousIniSettings();
    }

    protected function restorePreviousIniSettings(): void {
        if (null === $this->oldIniSettings) {
            return;
        }
        ini_set('display_errors', $this->oldIniSettings['display_errors']);
        ini_set('display_startup_errors', $this->oldIniSettings['display_startup_errors']);
    }

    public function handleError($severity, $message, $filePath, $lineNo): void {
        if ($severity & error_reporting()) {
            throw self::errorToException($severity, $message, $filePath, $lineNo);
        }
    }

    /**
     * @TODO: Check can we catch the E_ERROR, E_CORE_ERROR, E_PARSE errors, if yes, delete this method,
     * as they can will be handled by the handleError().
     */
    public function handleFatalError(): void {
        $error = error_get_last();
        error_clear_last();
        if ($this->fatalErrorHandlerActive
            && $error
            // [See for list of errors](https://wiki.php.net/rfc/engine_exceptions_for_php7#summary_of_current_error_model)
            && in_array(
                $error['type'],
                [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_PARSE]
            )
        ) {
            $this->handleException(
                self::errorToException($error['type'], $error['message'], $error['file'], $error['line'])
            );
            if ($this->exitOnFatalError) {
                exit(1);
            }
        }
    }

    public function registerAsFatalErrorHandler(bool $flag = null): bool {
        if (null !== $flag) {
            $this->registerAsFatalErrorHandler = $flag;
        }
        return $this->registerAsFatalErrorHandler;
    }

    public function exitOnFatalError(bool $flag = null): bool {
        if (null !== $flag) {
            $this->exitOnFatalError = $flag;
        }
        return $this->exitOnFatalError;
    }
}
