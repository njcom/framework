<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Morpho\Base\IFn;
use Morpho\Base\IHasServiceManager;
use Morpho\Base\THasServiceManager;
use Morpho\Tech\Php\ErrorHandler;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class DispatchErrorHandler implements IHasServiceManager, IFn {
    use THasServiceManager;

    public bool $throwErrors = false;
    public array $exceptionHandler;

    private array $thrownExceptions = [];

    public function __invoke(mixed $request): mixed {
        $exception = $request['error'];
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

        $request->handler = $exceptionHandler;
        $request->handled = false;
        //$request['error'] = $exception;
        $request->response->statusCode = Response::INTERNAL_SERVER_ERROR_STATUS_CODE;

        return $request;
    }

    protected function logError(Throwable $exception): void {
        $errorLogger = $this->serviceManager['errorLogger'];
        $errorLogger->emergency($exception, ['exception' => $exception]);
    }
}
