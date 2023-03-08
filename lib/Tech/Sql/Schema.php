<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Sql;

abstract class Schema implements ISchema {
    protected IClient $db;

    public function __construct(IClient $db) {
        $this->db = $db;
    }
    /*
        abstract public function createDatabase(string $dbName): void;

        public function deleteTables(iterable $tableNames): void {
            foreach ($tableNames as $tableName) {
                $this->deleteTable($tableName);
            }
        }

        public function deleteAllTables(): void {
            $this->deleteTables($this->tableNames());
        }

        abstract public function tableExists(string $tableName): bool;

        abstract public function deleteTable(string $tableName): void;

        abstract public function createTableSql(string $tableName): string;

        abstract public function deleteTableIfExists(string $tableName): void;

        /**
         * @return iterable<string>
        abstract public function tableNames(): iterable;
    */
}
