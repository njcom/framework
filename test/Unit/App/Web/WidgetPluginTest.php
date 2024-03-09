<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\View\WidgetPlugin;
use Morpho\Base\NotImplementedException;
use Morpho\Base\ServiceManager;
use Morpho\Testing\TestCase;

class WidgetPluginTest extends TestCase {
    public function testE_DelegatesToTemplateEngine() {
        $plugin = new class extends WidgetPlugin {
            public function runE($text) {
                return $this->e($text);
            }

            public function __toString(): string {
                throw new NotImplementedException();
            }
        };
        $templateEngine = new class {
            public array $eArgs = [];
            public function e(...$args): string {
                $this->eArgs = $args;
                return 'abc';
            }
        };
        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->expects($this->atLeastOnce())
            ->method('offsetGet')
            ->willReturnCallback(function ($id) use ($templateEngine) {
                $this->assertSame('templateEngine', $id);
                return $templateEngine;
            });
        $plugin->setServiceManager($serviceManager);
        $args = [null];
        $this->assertSame('abc', $plugin->runE(...$args));
        $this->assertSame($args, $templateEngine->eArgs);

        $args = ['foo'];
        $this->assertSame('abc', $plugin->runE(...$args));
        $this->assertSame($args, $templateEngine->eArgs);
    }
}