<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Testing;

use Morpho\Fs\Dir;
use PDO;

use function basename;
use function dirname;
use function pathinfo;

trait TDbTestCase {
    /**
     * @throws \ReflectionException
     */
    protected function createFixtures($db): void {
        $testDirPath = $this->getTestDirPath();
        if (is_dir($testDirPath)) {
            foreach (Dir::paths($testDirPath) as $path) {
                if (!preg_match('~Fixture\.php$~', $path)) {
                    continue;
                }
                require_once $path;
                $class = $this->ns() . '\\' . basename(dirname($path)) . '\\' . pathinfo($path, PATHINFO_FILENAME);
                (new $class())->load($db);
            }
        }
    }

    abstract protected function ns(): string;

    protected function mkPdo(array $conf = null): PDO {
        if (!$conf) {
            $conf = $this->dbConf();
        }
        $dsn = $conf['driver'] . ':dbname=' . $conf['db'] . ';' . $conf['host'] . ';' . ($conf['charset'] ?? 'utf8');
        $pdo = new PDO($dsn, $conf['user'], $conf['password'], $conf['pdoConf'] ?? []);
        $pdoConf = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => 0,
            PDO::ATTR_STRINGIFY_FETCHES  => 0,
        ];
        foreach ($pdoConf as $name => $val) {
            $pdo->setAttribute($name, $val);
        }
        return $pdo;
    }

    protected function dbConf(): array {
        return $this->sutConf()->dbConf();
    }

    abstract protected function sutConf(): mixed;
}