<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Monolog\Logger;
use Morpho\App\Web\DispatchErrorHandler;
use Morpho\App\Web\Request;
use Morpho\Base\IFn;
use Morpho\Base\ServiceManager;
use Morpho\Base\IHasServiceManager;
use Morpho\Testing\TestCase;
use PHPUnit\Framework\Attributes\BackupGlobals;
use RuntimeException;
use Throwable;

#[BackupGlobals(enabled: true)]
class DispatchErrorHandlerTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/';
    }

    public function testInterface() {
        $dispatchErrorHandler = new DispatchErrorHandler();
        $this->assertInstanceOf(IFn::class, $dispatchErrorHandler);
        $this->assertInstanceOf(IHasServiceManager::class, $dispatchErrorHandler);
    }

    public function testHandleException_ThrowsExceptionWhenTheSameErrorOccursTwice() {
        $handler = ['morpho-os/system', 'SomeCtrl', 'foo'];
        $dispatchErrorHandler = new DispatchErrorHandler();
        $dispatchErrorHandler->exceptionHandler = $handler;
        $exception = new RuntimeException();
        $this->checkHandlesTheSameErrorOccurredTwice($dispatchErrorHandler, $handler, $exception, 500, true);
    }

    public function testHandleException_MustRethrowExceptionIfThrowErrorsIsSet() {
        $exception = new RuntimeException('Uncaught test');
        $dispatchErrorHandler = new DispatchErrorHandler();
        $request = new Request();
        $request->isHandled = true;
        $exceptionMessage = $exception->getMessage();
        $dispatchErrorHandler->throwErrors = true;
        $serviceManager = $this->mkServiceManagerWithLogger(true, $exception, 1);
        $dispatchErrorHandler->setServiceManager($serviceManager);
        $request['error'] = $exception;
        try {
            $dispatchErrorHandler->__invoke($request);
            $this->fail('Must throw an exception');
        } catch (RuntimeException $e) {
            $this->assertSame([], $request->handler);
            $this->assertSame($exception, $e);
            $this->assertSame($exceptionMessage, $e->getMessage());
            $this->assertTrue($request->isHandled); // break the main loop
        }
    }

    private function checkHandlesTheSameErrorOccurredTwice(DispatchErrorHandler $dispatchErrorHandler, array $expectedHandler, Throwable $exception, int $expectedStatusCode, bool $mustLogError) {
        $request = new Request();
        $request->isHandled = true;;
        $request['error'] = $exception;

        $serviceManager = $this->mkServiceManagerWithLogger($mustLogError, $exception, 2);

        $dispatchErrorHandler->setServiceManager($serviceManager);

        $this->assertSame($request, $dispatchErrorHandler->__invoke($request));

        $this->assertFalse($request->isHandled);
        $this->assertSame($expectedHandler, $request->handler);
        $this->assertSame($exception, $request['error']);
        $this->assertSame($expectedStatusCode, $request->response->statusLine->statusCode->value);

        try {
            $dispatchErrorHandler->__invoke($request);
            $this->fail('Exception was not thrown');
        } catch (RuntimeException $e) {
            $this->assertEquals('Exception loop has been detected', $e->getMessage());
            $this->assertEquals($e->getPrevious(), $exception);
        }
    }

    private function mkServiceManagerWithLogger(bool $mustLogError, Throwable $expectedException, int $expectedNumberOfCalls) {
        $errorLogger = $this->createMock(Logger::class);
        if ($mustLogError) {
            $errorLogger->expects($this->exactly($expectedNumberOfCalls))
                ->method('emergency')
                ->with($this->equalTo($expectedException), $this->equalTo(['exception' => $expectedException]));
        } else {
            $errorLogger->expects($this->never())
                ->method('emergency');
        }

        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->expects($this->any())
            ->method('offsetGet')
            ->with('errorLogger')
            ->willReturn($errorLogger);
        return $serviceManager;
    }
}
