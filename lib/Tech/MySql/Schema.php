<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\MySql;

use Morpho\Tech\Sql\Schema as BaseSchema;

class Schema extends BaseSchema {
    public const ENGINE = 'InnoDB';
    public const CHARSET = 'utf8';
    public const COLLATION = 'utf8_general_ci';

    public function dbNames(): iterable {
        return $this->db->eval('SHOW DATABASES')->column();
    }
    /**
     * Note: the all arguments will not be escaped and therefore SQL-injection is possible. It is responsibility
     * of the caller to provide safe arguments.
     * public function createDatabase(string $dbName, string $charset = null, string $collation = null): void {
     * $this->db->eval("CREATE DATABASE " . $this->db->query()->quoteIdentifier($dbName)
     * . " CHARACTER SET " . ($charset ?: self::CHARSET)
     * . " COLLATE " . ($collation ?: self::COLLATION)
     * );
     * }
     */
    /**
     * @param string $dbName Should not contain user input (SQL injection is possible).
     * @return bool
     */
    public function dbExists(string $dbName): bool {
        return $this->db->select()->columns($this->db->expr(1))->table('INFORMATION_SCHEMA.SCHEMATA')->where(
            'SCHEMA_NAME = ?',
            [$dbName]
        )->eval()->bool();
    }

    /**
     * @param string $tableName Should not contain user input (SQL injection is possible).
     * @return bool
     */
    public function tableExists(string $tableName): bool {
        // @TODO: Use `mysql` table?
        //return $this->db->eval('SELECT FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'mydb';
        return $this->db->eval('SHOW TABLES LIKE ' . $this->db->quote($tableName))->bool();
    }

    public function deleteAllTables(): void {
        $this->deleteTables($this->tableNames());
    }

    /**
     * @param iterable $tableNames Each table name should not contain user input (SQL injection is possible).
     */
    public function deleteTables(array $tableNames): void {
        if ($tableNames) {
            $this->db->exec(
                'SET FOREIGN_KEY_CHECKS=0; DROP TABLE IF EXISTS ' . implode(
                    ', ',
                    $this->db->quoteIdentifier(iterator_to_array($tableNames, false))
                ) . '; SET FOREIGN_KEY_CHECKS=1'
            );
        }
    }

    public function tableNames(): iterable {
        return $this->db->eval('SHOW TABLES')->column();
    }
    /*
                public function renameDatabase(string $oldName, string $newName): void {
                    throw new NotImplementedException();
                }
        
                /**
                 * Note: the all arguments will not be escaped and therefore SQL-injection is possible. It is responsibility
                 * of the caller to provide safe arguments.
                public function deleteDatabase(string $dbName): void {
                    $this->db->eval("DROP DATABASE " . $this->db->query()->quoteIdentifier($dbName));
                }
        
                public function sizeOfDatabases(): int {
                    throw new NotImplementedException();
                }
        
                /**
                 * Returns size of the $dbName in bytes.
                 * Note: the all arguments will not be escaped and therefore SQL-injection is possible. It is responsibility
                 * of the caller to provide safe arguments.
                public function sizeOfDatabase(string $dbName): int {
                    return (int) $this->db->eval('SELECT SUM(DATA_LENGTH + INDEX_LENGTH)
                        FROM information_schema.TABLES
                        WHERE TABLE_SCHEMA = ?',
                        [$dbName]
                    )->field();
                }
        
                public function userExists(string $userName): bool {
                    return $this->db->eval('SELECT 1 FROM mysql.user WHERE User = ?', [$userName])->bool();
                }
        
                public function deleteTable(string $tableName): void {
                    $this->db->transaction(function ($db) use ($tableName) {
                        /** @var DbClientFactory $db */
    /*
        $isMySql = $this->connection->getDriver() instanceof MySqlDriver;
        if ($isMySql) {
        * /
        $db->eval('SET FOREIGN_KEY_CHECKS=0;');
        $db->eval('DROP TABLE IF EXISTS ' . $this->db->query()->quoteIdentifier($tableName));
        /*
        if ($isMySql) {
        }
        * /
        $db->eval('SET FOREIGN_KEY_CHECKS=1;');
    });
    }
    
    public function renameTable(string $oldTableName, string $newTableName): void {
    throw new NotImplementedException();
    }
    
    /**
    * Note: the all arguments will not be escaped and therefore SQL-injection is possible. It is responsibility
    * of the caller to provide safe arguments.
    * /
    public function tableDefinition(string $tableName, string $dbName = null): array {
    // The code fragment from the Doctrine MySQL, @TODO: specify where
    $stmt = $this->db->eval(
        "SELECT
            COLUMN_NAME AS Field,
            COLUMN_TYPE AS Type,
            IS_NULLABLE AS `Null`,
            COLUMN_KEY AS `Key`,
            COLUMN_DEFAULT AS `Default`,
            EXTRA AS Extra,
            COLUMN_COMMENT AS Comment,
            CHARACTER_SET_NAME AS CharacterSet,
            COLLATION_NAME AS Collation
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = " . (null === $dbName ? 'DATABASE()' : "'$dbName'") . " AND TABLE_NAME = '" . $tableName . "'"
    );
    if (!$stmt->rowCount()) {
        throw new RuntimeException("The table '" . (null === $dbName ? $tableName : $dbName . '.' . $tableName) . "' does not exist");
    }
    return $stmt->rows();
    }
    
    /**
    * Note: the all arguments will not be escaped and therefore SQL-injection is possible. It is responsibility
    * of the caller to provide safe arguments.
    * /
    public function createTableSql(string $tableName): string {
    return $this->db->eval("SHOW CREATE TABLE " . $this->db->query()->quoteIdentifier($tableName))
        ->row()['Create Table'];
    }
    
    public function optionsForCreateTableStmt(): string {
    //CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB
    return "ENGINE=" . self::ENGINE . " DEFAULT CHARSET=" . self::CHARSET;
    }
    
    public function viewNames(): array {
    throw new NotImplementedException();
    // SELECT TABLE_NAME FROM information_schema.VIEWS;
    }
    
    /**
    * Returns size of all tables in $dbName in bytes.
    * Note: the all arguments will not be escaped and therefore SQL-injection is possible. It is responsibility
    * of the caller to provide safe arguments.
    * /
    public function sizeOfTables(string $dbName): array {
    return $this->db->eval(
        'SELECT TABLE_NAME AS tableName,
        TABLE_TYPE AS tableType,
        DATA_LENGTH + INDEX_LENGTH as sizeInBytes
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = ? ORDER BY sizeInBytes DESC',
        [$dbName]
    )->rows();
    }
    
    /**
    * Returns a size of the $tableName in bytes.
    * The $tableName can contain dot (.) to refer to any table, e.g.: 'mysql.user'.
    * /
    public function sizeOfTable(string $tableName): int {
    throw new NotImplementedException();
    }
    
    public function renameColumn(): void {
    throw new NotImplementedException();
    }
    
    /**
    * @param string $constraint, e.g:
    *     "LIKE 'latin%'"
    *     or
    *     "SHOW CHARACTER SET WHERE CHARSET IN ('koi8r', 'latin1', 'utf8', 'binary')";
    * @return iterable of Charset.
    * /
    public function availableCharsetsOfServer(string $constraint = null): iterable {
    $sql = 'SHOW CHARACTER SET';
    if ($constraint) {
        $sql .= ' ' . $constraint;
    }
    foreach ($this->db->eval($sql) as $row) {
        yield new ServerCharset(
            $row['Charset'],
            $row['Default collation'],
            (int)$row['Maxlen'],
            $row['Description']
        );
    }
    }
    
    /**
    * @param string $constraint E.g: "WHERE CHARSET = 'utf8'"
    * @return iterable of available collations for the given charset or all available collations (if $charset is null), which were set on the server level.
    * /
    public function availableCollationsOfServer(string $constraint = null): iterable {
    $sql = 'SHOW COLLATION';
    if ($constraint) {
        $sql .= ' ' . $constraint;
    }
    $res = $this->db->eval($sql);
    foreach ($res as $row) {
        yield new ServerCollation(
            $row['Collation'],
            $row['Charset'],
            strtolower($row['Default']) === 'yes'
        );
    }
    }
    
    /**
    * The server character set and collation are used as default values if the database character set and collation are not specified in CREATE DATABASE statements (https://dev.mysql.com/doc/refman/5.7/en/charset-server.html).
    * NB: collation starts with charset, so to get server charset use the $collation->name() and check prefix or use $collation->charsetName().
    * /
    public function collationOfServer(): ServerCollation {
    $vars = $this->varsLike('collation_server', true);
    $collation = reset($vars);
    return head($this->availableCollationsOfServer('LIKE ' . $this->db->quote($collation)));
    }
    
    public function collationOfDatabase(string $dbName): DbCollation {
    // https://dev.mysql.com/doc/refman/5.7/en/charset-database.html
    $row = $this->db->eval('SELECT DEFAULT_CHARACTER_SET_NAME AS charset,
        DEFAULT_COLLATION_NAME AS collation
        FROM information_schema.SCHEMATA
        WHERE SCHEMA_NAME = ?',
        [$dbName]
    )->row();
    return new DbCollation($row['collation'], $row['charset'], $dbName);
    }
    
    /*
    public function setCharsetAndCollationOfDatabase(string $dbName, string charsetName, string $collationName): void {
      throw new NotImplementedException();
      // ALTER DATABASE $dbName CHARACTER SET $charset COLLATE $collation;
    }
    * /
    
    public function collationOfTables(string $dbName, array $tableNames = null): iterable {
    $whereClause = ' WHERE TABLE_SCHEMA = ?';
    $whereArgs = [$dbName];
    if ($tableNames) {
        $whereClause .= ' AND TABLE_NAME IN (' . $this->db->query()->positionalPlaceholdersStr($tableNames) . ')';
        $whereArgs = array_merge($whereArgs, $tableNames);
    }
    $res = $this->db->eval('SELECT TABLE_SCHEMA AS dbName,
        TABLE_NAME AS tableName,
        TABLE_TYPE AS tableType,
        SUBSTRING(TABLE_COLLATION, 1, LOCATE("_", TABLE_COLLATION) - 1) AS charset,
        TABLE_COLLATION AS collation
        FROM information_schema.TABLES' . $whereClause,
        $whereArgs
    );
    foreach ($res as $row) {
        yield new TableCollation($row['collation'], $row['charset'], $row['tableName'], $row['dbName']);
    }
    }
    
    /**
    * @param string $dbName It can be either a database name or the table name.
    * @param string $tableName
    * /
    public function collationOfTable(string $dbName, string $tableName = null): TableCollation {
    if (null === $tableName) {
        [$dbName, $tableName] = explode('.', $dbName);
    }
    return head($this->collationOfTables($dbName, [$tableName]));
    }
    
    public function setCharsetAndCollationOfTable(string $tableName): array {
    // https://dev.mysql.com/doc/refman/5.7/en/charset-table.html
    // ALTER TABLE $tableName CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
    // ALTER TABLE $tableName CHARACTER SET utf8, COLLATE utf8_general_ci;
    throw new NotImplementedException();
    }
    
    /**
    * The $tableName can contain dot (.) to refer to any table, e.g.: 'mysql.user'.
    * /
    public function collationOfColumns(string $dbName, string $tableName, string $columnName): array {
    // https://dev.mysql.com/doc/refman/5.7/en/charset-column.html
    throw new NotImplementedException();
    // SHOW FULL COLUMNS FROM table_name;
    /*
    SELECT TABLE_SCHEMA,
      TABLE_NAME,
      CCSA.CHARACTER_SET_NAME AS DEFAULT_CHAR_SET,
      COLUMN_NAME,
      COLUMN_TYPE,
      C.CHARACTER_SET_NAME
    FROM information_schema.TABLES AS T
      JOIN information_schema.COLUMNS AS C USING (TABLE_SCHEMA, TABLE_NAME)
      JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY AS CCSA
        ON (T.TABLE_COLLATION = CCSA.COLLATION_NAME)
    WHERE TABLE_SCHEMA='$dbName'
          AND C.DATA_TYPE IN ('enum', 'varchar', 'char', 'text', 'mediumtext', 'longtext')
    ORDER BY TABLE_SCHEMA,
      TABLE_NAME,
      COLUMN_NAME
    ;
     * /
    }
    
    public function setCharsetAndCollationOfColumn(): void {
    //ALTER TABLE $tableName CHANGE COLUMN $columnName $columnName TEXT CHARACTER SET utf8 COLLATE utf8_general_ci;
    // SHOW FULL COLUMNS FROM $tableName;
    throw new NotImplementedException();
    }
    
    public function charsetAndCollationVars(): array {
    return array_merge($this->charsetVars(), $this->collationVars());
    }
    
    public function charsetVars(): array {
    return $this->varsWithPrefix('character_set');
    }
    
    public function collationVars(): array {
    return $this->varsWithPrefix('collation');
    }
    
    public function varsWithPrefix(string $prefix): array {
    return $this->db->doWithEmulatedPrepares(function () use ($prefix) {
        // The `SHOW VARIABLES` is not in [SQL Syntax Allowed in Prepared Statements](https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-prepared-statements.html#idm139630090954512) so we need to emulate prepared statements.
        return $this->db->eval('SHOW VARIABLES LIKE ?', [str_replace('%', '\%', $prefix) . '%'])->map();
    });
    }
    
    public function varsWithSuffix(string $suffix): array {
    // The `SHOW VARIABLES` is not in [SQL Syntax Allowed in Prepared Statements](https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-prepared-statements.html#idm139630090954512) so we need to emulate prepared statements.
    return $this->db->doWithEmulatedPrepares(function () use ($suffix) {
        return $this->db->eval('SHOW VARIABLES LIKE ?', ['%' . str_replace('%', '\%', $suffix)])->map();
    });
    }
    
    public function varsLike(string $infix, bool $exactMatch = false): array {
    return $this->db->doWithEmulatedPrepares(function () use ($infix, $exactMatch) {
        if ($exactMatch) {
            return $this->db->eval('SHOW VARIABLES LIKE ?', [str_replace('%', '\%', $infix)])->map();
        }
        return $this->db->eval('SHOW VARIABLES LIKE ?', ['%' . str_replace('%', '\%', $infix) . '%'])->map();
    });
    }
    
    public function createUser($name, $password, $host): void {
    throw new NotImplementedException();
    //       GRANT CREATE, DROP, LOCK TABLES, REFERENCES, ALTER, DELETE, INDEX, INSERT, SELECT, UPDATE, CREATE TEMPORARY TABLES, TRIGGER, CREATE VIEW, SHOW VIEW, ALTER ROUTINE, CREATE ROUTINE, EXECUTE ON $dbName.* to $userName@$hostName IDENTIFIED BY '$password';
    //FLUSH PRIVILEGES;
    }
    
    public function deleteUser(): void {
    throw new NotImplementedException();
    }
    */
}