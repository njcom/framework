<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Sql;

interface ISelectQuery extends IQuery {
    public function columns(array|Expr|string $columns): static;

    public function sql(): string;

    public function into(): static;

    public function union(): static;

    public function leftJoin(string|Expr $sql): static;

    public function innerJoin(string|Expr $sql): static;

    public function rightJoin(string|Expr $sql): static;

    public function groupBy(string|Expr $sql): static;

    public function having(string|Expr $sql): static;

    public function orderBy(string|Expr|array $orderBy): static;

    public function limit(int $numOfRows, int $offset = null): static;
}