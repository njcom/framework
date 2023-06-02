<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Testing;

use ArrayObject;
use Morpho\Base\TSingleton;

use function getenv;

// SUT/System Under Test
class SutConf extends ArrayObject {
    use TSingleton;

    public function __construct(array $vals = []) {
        if (!isset($vals['timezone'])) {
            $vals['timezone'] = 'UTC';
        }
        if (!isset($vals['dbConf'])) {
            $vals['dbConf'] = [
                'driver'   => 'mysql',
                'host'     => getenv('MORPHO_TEST_DB_HOST') ?: '127.0.0.1',
                'user'     => getenv('MORPHO_TEST_DB_USER') ?: 'root',
                'password' => getenv('MORPHO_TEST_DB_PASSWORD') ?: '',
                'db'       => getenv('MORPHO_TEST_DB_DB') ?: 'test',
            ];
        }
        parent::__construct($vals);
    }

    /*
    protected string $uriAuthority;



    public function webServerWebDirPath(): string {
        return $this->baseDirPath() . '/' . FRONTEND_DIR_NAME;
    }

    public function siteUri(): string {
        $webServerAddress = $this->webServerAddress();
        return 'http://' . $webServerAddress['host'] . ':' . $webServerAddress['port'];
    }

    public function webServerAddress(): array {
        $domain = getenv('MORPHO_TEST_WEB_SERVER_DOMAIN') ?: 'framework';
        $port = getenv('MORPHO_TEST_WEB_SERVER_PORT') ?: 80;
        return ['host' => $domain, 'port' => (int) $port];
    }

    public function webDriverConf(): array {
        $geckoBinFilePath = $this->testRcDirPath() . '/geckodriver';
        $geckoBinCandidateFilePath = getenv('MORPHO_GECKO_BIN_FILE_PATH');
        if (false !== $geckoBinCandidateFilePath && file_exists($geckoBinCandidateFilePath)) {
            $geckoBinFilePath = $geckoBinCandidateFilePath;
        }
        return ['geckoBinFilePath' => $geckoBinFilePath];
    }

    public function testRcDirPath(): string {
        return getenv('MORPHO_TEST_RC_DIR_PATH') ?: $this->baseDirPath() . '/' . TEST_DIR_NAME . '/Integration';
    }
    */

    public function dbConf(): array {
        return $this['dbConf'];
    }
}
