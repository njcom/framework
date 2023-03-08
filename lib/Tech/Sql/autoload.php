<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Sql;

use Morpho\Tech\MySql\Client as MySqlClient;
use Morpho\Tech\Sqlite\Client as SqliteClient;
use UnexpectedValueException;

const SQL_TRUE = 1;
const SQL_FALSE = 0;

function mkDbClient(array $conf = null): IClient {
    $driverName = $conf['driver'] ?? 'mysql';
    unset($conf['driver']);
    switch ($driverName) {
        case 'mysql':
            return new MySqlClient($conf);
        case 'sqlite':
            return new SqliteClient($conf);
    }
    throw new UnexpectedValueException("Unknown DB driver");
}