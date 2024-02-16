<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use Morpho\Base\Env;
use Morpho\Base\Event;
use Morpho\Base\ServiceManager;
use Morpho\Base\EventManager;
use Morpho\Tech\Php\ErrorHandler;
use Throwable;

use function addslashes;
use function error_log;
use function umask;

class App extends EventManager {
    public readonly array $conf;
    private ?ServiceManager $serviceManager = null;

    public function __construct(array $conf = null) {
        $this->conf = $conf ?? [];
    }

    public function init(): ServiceManager {
        if ($this->serviceManager) {
            // Already initialized.
            return $this->serviceManager;
        }
        return $this->serviceManager = $this->_init();
    }

    public function run(): mixed {
        try {
            $serviceManager = $this->init();
            $site = $serviceManager['site'];

            $response = $site->__invoke($serviceManager);

            $exitCode = $response ? Env::SUCCESS_CODE : Env::FAILURE_CODE;
            $event = new Event('exit', ['exitCode' => $exitCode, 'response' => $response]);
            $this->trigger($event);
            return $event['exitCode'];
        } catch (Throwable $e) {
            $this->handleException($e);
        }
        return Env::FAILURE_CODE;
    }

    protected function handleException(Throwable $e): void {
        if (Env::boolIniVal('display_errors')) {
            /** @noinspection PhpStatementHasEmptyBodyInspection */
            while (@ob_end_clean());
            echo $e;
        }
        if (ErrorHandler::isErrorLogEnabled()) {
            // @TODO: check how error logging works on PHP core level, remove unnecessary calls and checks.
            error_log(addslashes((string)$e));
        }
    }

    protected function _init(): ServiceManager {
        /** @var SiteFactory $siteFactory */
        $siteFactory = $this->conf['siteFactory']($this);
        $site = $siteFactory->__invoke();

        $siteConf = $site->conf();

        $serviceManager = $siteConf['serviceManager'];
        $serviceManager['app'] = $this;
        $serviceManager['site'] = $site;
        $serviceManager->conf = $siteConf['services'];

        if (isset($siteConf['umask'])) {
            umask($siteConf['umask']);
        }

        /** @var AppInitializer $appInitializer */
        $appInitializer = $serviceManager['appInitializer'];
        $appInitializer->init();

        return $serviceManager;
    }
}
