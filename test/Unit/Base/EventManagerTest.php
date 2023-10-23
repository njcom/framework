<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Base;

use ArrayObject;
use Morpho\Base\Event;
use Morpho\Base\EventManager;
use Morpho\Testing\TestCase;

class EventManagerTest extends TestCase {
    public function testEventArgs() {
        $event = new Event('foo', ['bar' => 'pizza']);
        $this->assertInstanceOf(ArrayObject::class, $event);
        $this->assertSame('foo', $event->name);
        $this->assertSame(['bar' => 'pizza'], $event->getArrayCopy());
        $event->exchangeArray(['some' => 123]);
        $this->assertSame(['some' => 123], $event->getArrayCopy());
    }

    public function testApi() {
        $eventName = 'my-event';
        $eventManager = new EventManager();
        $handler1 = function (...$args) use (&$args1) {
            $args1 = $args;
        };
        $handler2 = function (...$args) use (&$args2) {
            $args2 = $args;
        };
        $handler3 = function (...$args) use (&$args3) {
            $args3 = $args;
        };
        $eventManager->on($eventName, $handler1);
        $eventManager->on($eventName, $handler2);
        $eventManager->on($eventName, $handler3);
        $eventManager->on(
            'should-not-call',
            function () {
                $this->fail('Should not be called');
            }
        );

        $event = new Event($eventName, ['foo' => 'bar']);

        $eventManager->trigger($event);

        $this->assertSame([$event], $args1);
        $this->assertSame([$event], $args2);
        $this->assertSame([$event], $args3);

        $args1 = $args2 = $args3 = null;

        $eventManager->off(
            $eventName,
            function ($handler) use ($handler1) {
                return $handler === $handler1;
            }
        );
        $eventManager->trigger($event);

        $this->assertNull($args1);
        $this->assertSame([$event], $args2);
        $this->assertSame([$event], $args3);

        $args1 = $args2 = $args3 = null;

        $eventManager->off($eventName);
        $eventManager->trigger($event);

        $this->assertNull($args1);
        $this->assertNull($args2);
        $this->assertNull($args3);
    }

    public function testCanChangeFieldsOfEvent() {
        $event = new Event('change', ['foo' => 'bar']);
        $eventManager = new EventManager();
        $eventManager->on('change', fn ($event) => $event['foo'] = 'abc');
        $this->assertSame('bar', $event['foo']);
        $eventManager->trigger($event);
        $this->assertSame('abc', $event['foo']);
    }

    public function testOff_NonExistingHandlerDoesNotThrowError() {
        $eventManager = new EventManager();
        $eventManager->off(
            'foo',
            function () {
            }
        );
        $this->markTestAsNotRisky();
    }
}
