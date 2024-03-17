<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Base;

abstract class HavingServiceManager implements IHasServiceManager {
    protected ServiceManager $serviceManager;

    /**
     * @notAction
     */
    public function setServiceManager(ServiceManager $serviceManager): void {
        $this->serviceManager = $serviceManager;
    }
}