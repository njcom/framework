<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Sql;

use Stringable;

interface IQuery extends Stringable {
    public function table(string|array|Expr $tableName): static;

    public function expr($expr): Expr;

    public function sql(): string;

    public function args(): array;

    /**
     * @param array|\Stringable|string $condition
     * @param array|string|int|null    $args If not null will be casted to array
     * @return $this
     */
    public function where(array|Stringable|string $condition, null|array|string|int $args = null): static;

    public function eval(): Result;

    /**
     * Builds (configures) query from the specification.
     */
    public function build(array $spec): static;
}
