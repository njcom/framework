<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App;

use Morpho\App\IResponse;
use Morpho\App\Message;
use Morpho\App\Response;

class ResponseTest extends MessageTest {
    private IResponse $response;

    protected function setUp(): void {
        parent::setUp();
        $this->response = new class extends Response {

        };
    }

    public function testBodyAccessors() {
        $this->assertTrue($this->response->isBodyEmpty());
        $this->assertSame('', $this->response->body());
        $newBody = 'foo';
        $this->assertNull($this->response->setBody($newBody));
        $this->assertSame($newBody, $this->response->body());
        $this->assertFalse($this->response->isBodyEmpty());
    }

    protected function mkMessage(): Message {
        return clone $this->response;
    }
}
