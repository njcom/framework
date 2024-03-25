<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App;

use Morpho\App\IMessage;
use Morpho\Testing\TestCase;

abstract class MessageTest extends TestCase {
    public function testMessage() {
        $message = $this->mkMessage();
        $message['foo'] = 'bar';
        $this->assertSame(['foo' => 'bar'], $message->getArrayCopy(), 'Properties should be ignored');
    }

    public function testInterface() {
        $this->assertInstanceOf(IMessage::class, $this->mkMessage());
    }

    abstract protected function mkMessage(): IMessage;
}

