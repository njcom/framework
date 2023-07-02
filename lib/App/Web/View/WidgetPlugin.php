<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use Morpho\Base\IHasServiceManager;
use Morpho\Base\IServiceManager;
use Stringable;

abstract class WidgetPlugin extends Plugin implements IHasServiceManager, Stringable {
    protected IServiceManager $serviceManager;

    public function setServiceManager(IServiceManager $serviceManager): static {
        $this->serviceManager = $serviceManager;
        return $this;
    }

    protected function e(string|int|Stringable $text): string {
        $templateEngine = $this->serviceManager['templateEngine'];
        return $templateEngine->e($text);
    }
}