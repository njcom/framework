<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\Controller;
use Morpho\Base\IHasServiceManager;
use Morpho\Testing\TestCase;

class ControllerTest extends TestCase {
    public function testInterface() {
        $this->assertInstanceOf(
            IHasServiceManager::class,
            new class extends Controller {
            }
        );
    }
}
