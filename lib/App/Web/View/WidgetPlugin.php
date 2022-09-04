<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use Morpho\Base\IHasServiceManager;
use Morpho\Base\IServiceManager;
use Morpho\Base\NotImplementedException;

class WidgetPlugin extends Plugin implements IHasServiceManager {
    private $serviceManager;

    public function __invoke($value) {
        throw new NotImplementedException();
        /*
        $name = $args[0];
        if ($name !== 'Menu') {
        }
        $request = $this->serviceManager['request'];
        return new MenuWidget(
            $this->serviceManager['db'],
            $request->baseRelUri(),
            $request->requestUri()
        );
        */
    }

    public function setServiceManager(IServiceManager $serviceManager): static {
        $this->serviceManager = $serviceManager;
        return $this;
    }
}

