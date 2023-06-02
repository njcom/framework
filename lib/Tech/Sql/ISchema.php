<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Sql;

interface ISchema {
    public function dbNames(): iterable;

    public function dbExists(string $dbName): bool;

    public function tableNames(): iterable;

    public function tableExists(string $tableName): bool;

    public function deleteAllTables(): void;

    public function deleteTables(array $tableNames): void;
}