<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Morpho\Base\IHasServiceManager;
use Morpho\Base\THasServiceManager;
use Morpho\Tech\Php\ErrorHandler;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class DispatchErrorHandler implements IHasServiceManager {
    use THasServiceManager;

    private $thrownExceptions = [];

    private $throwErrors = false;

    private $exceptionHandler;

    public function throwErrors(bool $flag = null): bool {
        if (null !== $flag) {
            $this->throwErrors = $flag;
        }
        return $this->throwErrors;
    }

    public function setExceptionHandler(array $handler): void {
        $this->exceptionHandler = $handler;
    }

    public function handleException(Throwable $exception, Request $request): void {
        $this->logError($exception);

        if ($this->throwErrors) {
            throw $exception;
        }

        $exceptionHandler = $this->exceptionHandler;
        if (!$exceptionHandler) {
            throw new UnexpectedValueException('Empty exception handler');
        }

        foreach ($this->thrownExceptions as $prevException) {
            if (ErrorHandler::hashId($prevException) === ErrorHandler::hashId($exception)) {
                throw new RuntimeException('Exception loop has been detected', 0, $exception);
            }
        }
        $this->thrownExceptions[] = $exception;

        $request->setHandler($exceptionHandler);
        $request->isHandled(false);
        $request['error'] = $exception;
        $request->response()->setStatusCode(Response::INTERNAL_SERVER_ERROR_STATUS_CODE);
    }

    protected function logError(Throwable $exception): void {
        $errorLogger = $this->serviceManager['errorLogger'];
        $errorLogger->emergency($exception, ['exception' => $exception]);
    }
}
