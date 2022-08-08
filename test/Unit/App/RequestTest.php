<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App;

use Morpho\App\IRequest;
use Morpho\App\IResponse;
use Morpho\App\Message;
use Morpho\App\Request;
use Morpho\Base\NotImplementedException;

class RequestTest extends MessageTest {
    private $request;

    protected function setUp(): void {
        parent::setUp();
        $this->request = new class extends Request {
            public function response(): IResponse {
                throw new NotImplementedException();
            }
        };
    }

    public function testInterface() {
        $this->assertInstanceOf(IRequest::class, $this->request);
    }

    protected function mkMessage(): Message {
        return clone $this->request;
    }
}
