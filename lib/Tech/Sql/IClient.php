<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Sql;

interface IClient {
    public function setConf(array $conf): static;

    public function conf(): array;

    public function connect(): void;

    public function disconnect(): void;

    public function isConnected(): bool;

    public function expr(mixed $expr): Expr;

    public function where(array|string $condition, array $args = null): array;

    public function insert(array $spec = null): IInsertQuery;

    public function select(array $spec = null): ISelectQuery;

    public function update(array $spec = null): IUpdateQuery;

    public function delete(array $spec = null): IDeleteQuery;

    public function replace(array $spec = null): IReplaceQuery;

    public function exec(string $sql): int;

    public function eval(string $sql, array $args = null): Result;

    public function lastInsertId(string $name = null): string;

    /**
     * @param callable $transaction
     * @param mixed ...$args
     * @return mixed
     */
    public function transaction(callable $transaction, ...$args): mixed;

    public function inTransaction(): bool;

    public function schema(): ISchema;

    /**
     * @return string|null
     */
    public function dbName(): ?string;

    public function useDb(string $dbName): static;

    public function driverName(): string;

    public function availableDrivers(): array;

    /**
     * @param string|array|Expr $identifiers
     * @return string|array
     */
    public function quoteIdentifier(string|array|Expr $identifiers): string|array;

    /**
     * Returns SQL-query for quoted identifiers. If an array has been provided, then separates them with comma.
     */
    public function quoteIdentifierStr(string|array|Expr $identifiers): string;

    public function positionalArgs(array $args): array;

    // [':foo = ?', ':bar = ?']
    //public function namedArgs(array $args): array;

    public function nameValArgs(array $args): array;
    /*
        public function commaSep(array $exprs): string;

        public function logicalOr(array $exprs): string;

        public function logicalAnd(array $exprs): string;
    */
    /**
     * See [SQL Syntax Allowed in Prepared Statements](https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-prepared-statements.html#idm139630090954512)
     * @param callable $fn
     * @return mixed
     */
    public function usingEmulatedPrepares(callable $fn): mixed;
}