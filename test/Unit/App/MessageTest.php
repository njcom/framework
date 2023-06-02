<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App;

use ArrayObject;
use Morpho\App\Message;
use Morpho\Testing\TestCase;

abstract class MessageTest extends TestCase {
    public function testMessage() {
        $message = $this->mkMessage();
        $this->assertInstanceOf(ArrayObject::class, $message, 'Message is \\ArrayObject');

        $message->test = '123';
        $message['foo'] = 'bar';
        $this->assertSame(['foo' => 'bar'], $message->getArrayCopy(), 'Properties should be ignored');
    }

    abstract protected function mkMessage(): Message;
}
