<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use RuntimeException;

use function is_file;
use function Morpho\Base\merge;

class SiteFactory {
    protected IHostNameValidator $hostNameValidator;
    protected array $appConf;

    public function __construct(IHostNameValidator $hostNameValidator, array $appConf) {
        $this->hostNameValidator = $hostNameValidator;
        $this->appConf = $appConf;
    }

    /**
     * @throws \RuntimeException
     * @return \Morpho\App\ISite
     */
    public function __invoke(): ISite {
        $hostName = $this->hostNameValidator->currentHostName();
        foreach ($this->appConf['sites'] as $siteName => $siteConf) {
            if ($this->hostNameValidator->isValid($hostName)) {
                return $this->mkSite($siteName, $siteConf, $hostName);
            }
        }
        $this->hostNameValidator->throwInvalidSiteError();
    }

    protected function mkSite(string $siteName, array $siteConf, string $hostName): ISite {
        return new Site($siteName, $siteConf['module']['name'], $this->loadExtendedSiteConf($siteConf), $hostName);
    }

    protected function loadExtendedSiteConf(array $basicSiteConf): array {
        $siteModuleConf = $basicSiteConf['module'];
        // Site's config file can use site module's classes so enable autoloading for it.
        require $siteModuleConf['paths']['dirPath'] . '/' . VENDOR_DIR_NAME . '/autoload.php';
        $extendedSiteConf = $this->loadConfFile($siteModuleConf['paths']['confFilePath']);
        $siteModuleName = $siteModuleConf['name'];
        unset($siteModuleConf['name']);
        return merge([$siteModuleName => $siteModuleConf], $extendedSiteConf);
    }

    protected function loadConfFile(string $filePath): array {
        if (!is_file($filePath)) {
            throw new RuntimeException("Configuration file does not exist");
        }
        return require $filePath;
    }
}
