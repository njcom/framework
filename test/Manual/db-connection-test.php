<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Manual;

use PDO;

require __DIR__ . '/init.php';

(function ($host, $user, $password, $dbName) {
    $dsn = 'mysql:dbname=' . $dbName . ';' . $host . ';charset=UTF8';
    $db = new PDO($dsn, $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    foreach ($db->query('SHOW TABLES') as $tableName) {
        echo array_shift($tableName) . "\n";
    }
    /*
    $sqls = [
        'DROP DATABASE IF EXISTS test; CREATE DATABASE test DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci',
        "GRANT ALL PRIVILEGES ON test.* TO 'root'@'127.0.0.1' IDENTIFIED BY ''",
        "FLUSH PRIVILEGES",
    ];
    */
})(
    'localhost',
    'root',
    '',
    'test'
);
