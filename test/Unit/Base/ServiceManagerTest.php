<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use ArrayObject;
use Closure;
use Morpho\Base\IHasServiceManager;
use Morpho\Base\IServiceManager;
use Morpho\Base\ServiceManager;
use Morpho\Base\ServiceNotFoundException;
use Morpho\Testing\TestCase;
use RuntimeException;
use stdClass;

class ServiceManagerTest extends TestCase {
    private IServiceManager $serviceManager;

    protected function setUp(): void {
        parent::setUp();
        $this->serviceManager = new MyServiceManager();
    }

    public function testArrayAccess() {
        $this->assertInstanceOf(ArrayObject::class, $this->serviceManager);
        $id = __FUNCTION__;
        $value = 'bar';
        $this->assertFalse(isset($this->serviceManager[$id]));
        $this->serviceManager[$id] = $value;
        $this->assertSame($value, $this->serviceManager[$id]);
        $this->assertTrue(isset($this->serviceManager[$id]));
        unset($this->serviceManager[$id]);
        $this->assertFalse(isset($this->serviceManager[$id]));
    }

    public function testUnsetNonExistingItem_MethodDoesNotExist() {
        $serviceManager = new class extends ServiceManager {
        };
        unset($serviceManager['foo']);
        // should not throw an exception
        $this->markTestAsNotRisky();
    }

    public function testUnsetNonExistingItem_MethodExists() {
        $serviceManager = new class extends ServiceManager {
            public function mkFooService() {
                return new stdClass();
            }
        };
        unset($serviceManager['foo']);
        // should not throw an exception
        $this->markTestAsNotRisky();
    }

    public function testUnset_ExistingItem_NotCaseSensitive() {
        $serviceManager = new class extends ServiceManager {
        };
        $serviceManager['foobar'] = new stdClass();
        unset($serviceManager['FOOBar']);
        // should not throw an exception
        $this->assertTrue(!isset($serviceManager['foobar']));
        $this->assertTrue(!isset($serviceManager['FOOBar']));
    }

    public function testArrayAccess_OffsetExists_ReturnsTrueIfContainerCanReturnEntryForId() {
        // See [PHP docs for the ContainerInterface::has()](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md#31-psrcontainercontainerinterface)
        $this->assertTrue(isset($this->serviceManager['foo']));
    }

    public function testConstructor_SetServicesViaConstructor() {
        $service = new stdClass();

        $serviceManager = new ServiceManager(['foo' => $service]);

        $this->assertSame($service, $serviceManager['foo']);
    }

    public function testCanDetectCircularReference() {
        $this->expectException(RuntimeException::class, "Circular reference detected for the service 'foo', path: 'foo -> bar -> foo'");
        $this->serviceManager['foo'];
    }

    public function testReturnsTheSameInstance() {
        $obj1 = $this->serviceManager['obj'];
        $obj2 = $this->serviceManager['obj'];
        $this->assertSame($obj1, $obj2);
        $this->assertInstanceOf('\\stdClass', $obj1);
    }

    public function testThrowsExceptionWhenServiceNotFound() {
        $this->expectException(ServiceNotFoundException::class);
        $this->serviceManager['nonexistent'];
    }

    public function testCreateServiceMethodCanReturnClosure() {
        $closure = $this->serviceManager['myClosure'];
        $this->assertInstanceOf(Closure::class, $closure);
        $this->assertSame($closure, $this->serviceManager['myClosure']);
        $this->assertNull($this->serviceManager->closureCalledWith);
        $closure('my arg');
        $this->assertEquals('my arg', $this->serviceManager->closureCalledWith);
    }
}

class MyServiceManager extends ServiceManager {
    public $closureCalledWith;

    protected function mkObjService() {
        return new stdClass();
    }

    protected function mkFooService() {
        return $this['bar'];
    }

    protected function mkBarService() {
        return $this['foo'];
    }

    protected function mkMyClosureService() {
        return function ($foo) {
            $this->closureCalledWith = $foo;
        };
    }
}