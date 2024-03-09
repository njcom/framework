<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Morpho\Base\IHasServiceManager;
use Morpho\Base\ServiceManager;

abstract class Controller implements IHasServiceManager {
    protected ServiceManager $serviceManager;

    public function setServiceManager(ServiceManager $serviceManager): void {
        $this->serviceManager = $serviceManager;
    }

/*    protected function jsConf(): ArrayObject {
        if (!isset($this->request['jsConf'])) {
            $this->request['jsConf'] = new ArrayObject();
        }
        return $this->request['jsConf'];
    }

    protected function messenger(): View\Messenger {
        return $this->serviceManager['messenger'];
    }

    protected function handleResult(mixed $actionResult): mixed {
        if ($actionResult instanceof Result) {
            $response = $this->request->response();
            $response->allowAjax(true)
                ->setFormats([ContentFormat::JSON]);
        }
        return $actionResult;
    }*/
}
