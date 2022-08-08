<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App;

use Morpho\Base\Env;
use Morpho\Base\Event;
use Morpho\Base\EventManager;
use Morpho\Base\IServiceManager;
use Morpho\Tech\Php\ErrorHandler;
use Throwable;

use function addslashes;
use function error_log;
use function umask;

class App extends EventManager {
    protected array $conf;

    public function __construct($conf = null) {
        $this->setConf($conf ?: []);
    }

    public function setConf($conf): void {
        $this->conf = $conf;
    }

    public static function main($conf = null): int {
        try {
            $app = new static($conf);
            $response = $app->run();
            $exitCode = $response ? Env::SUCCESS_CODE : Env::FAILURE_CODE;
            $event = new Event('exit', ['exitCode' => $exitCode, 'response' => $response]);
            $app->trigger($event);
            return $event->args['exitCode'];
        } catch (Throwable $e) {
            if (Env::boolIniVal('display_errors')) {
                while (@ob_end_clean());
                echo $e;
            }
            self::logErrorFallback($e);
        }
        return Env::FAILURE_CODE;
    }

    /**
     * @return IResponse|false
     */
    public function run() {
        $serviceManager = $this->init();
        $site = $serviceManager['site'];
        return $site->__invoke($serviceManager);
    }

    public function init(): IServiceManager {
        /** @var SiteFactory $siteFactory */
        $siteFactory = $this->conf['siteFactory']($this);
        $site = $siteFactory->__invoke();

        $siteConf = $site->conf();

        $serviceManager = $siteConf['serviceManager'];
        $serviceManager['app'] = $this;
        $serviceManager['site'] = $site;
        $serviceManager->setConf($siteConf['services']);

        if (isset($siteConf['umask'])) {
            umask($siteConf['umask']);
        }

        /** @var AppInitializer $appInitializer */
        $appInitializer = $serviceManager['appInitializer'];
        $appInitializer->init();

        return $serviceManager;
    }

    protected static function logErrorFallback(Throwable $e): void {
        if (ErrorHandler::isErrorLogEnabled()) {
            // @TODO: check how error logging works on PHP core level, remove unnecessary calls and checks.
            error_log(addslashes((string) $e));
        }
    }

    public function conf() {
        return $this->conf;
    }
}
