<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\MySql;

use Morpho\Base\NotImplementedException;
use Morpho\Tech\Sql\Expr;
use Morpho\Tech\Sql\ISelectQuery;

class SelectQuery extends Query implements ISelectQuery {
    protected array $columns = [];
    protected array $join = [];
    protected array $groupBy = [];
    protected array $having = [];
    //protected array $window = [];
    protected array $orderBy = [];
    protected ?array $limit = null;

    public function columns(array|Expr|string $columns): static {
        if (is_array($columns)) {
            $this->columns = array_merge($this->columns, $columns);
        } else {
            $this->columns[] = $columns instanceof Expr ? $columns : new Expr($columns);
        }
        return $this;
    }

    public function sql(): string {
        $sql = [];
        $columns = [];

        $quoteIdentifier = fn ($column) => $column instanceof Expr ? $column->val() : $this->db->quoteIdentifier($column);

        if ($this->columns) {
            foreach ($this->columns as $column) {
                if (is_array($column)) {
                    $columns = array_merge($columns, $this->db->quoteIdentifier($column));
                } elseif ($column === '*') {
                    $columns[] = $column;
                } else {
                    $columns[] = $quoteIdentifier($column);
                }
            }
        }
        $sql[] = 'SELECT ' . ($columns ? implode(', ', $columns) : '*');
        if ($this->tables) {
            $sql[] = 'FROM ' . $this->tableRefStr();
        }
        foreach ($this->join as $join) {
            $sql[] = $join[0] . ' JOIN ' . $join[1];
        }
        if ($this->where) {
            $sql[] = $this->whereStr();
        }
        if ($this->groupBy) {
            $sql[] = 'GROUP BY ' . implode(', ', array_map($quoteIdentifier, $this->groupBy));
        }
        if ($this->having) {
            $sql[] = 'HAVING ' . implode(' AND ', array_map($quoteIdentifier, $this->having));
        }
        if ($this->orderBy) {
            $sql[] = 'ORDER BY ' . implode(', ', array_map($quoteIdentifier, $this->orderBy));
        }
        if ($this->limit) {
            [$offset, $numOfRows] = $this->limit;
            $sql[] = 'LIMIT ' . (null !== $offset ? intval($offset) . ', ' : '') . intval($numOfRows);
        }
        return implode("\n", $sql);
    }

    public function into(): static {
        throw new NotImplementedException();
    }

    public function union(): static {
        // https://dev.mysql.com/doc/refman/8.0/en/union.html
        throw new NotImplementedException();
    }

    public function leftJoin(string|Expr $sql): static {
        $this->join[] = ['LEFT', $sql];
        return $this;
    }

    public function innerJoin(string|Expr $sql): static {
        $this->join[] = ['INNER', $sql];
        return $this;
    }

    public function rightJoin(string|Expr $sql): static {
        $this->join[] = ['RIGHT', $sql];
        return $this;
    }

    public function groupBy(string|Expr $sql): static {
        $this->groupBy[] = $sql;
        return $this;
    }

    public function having(string|Expr $sql): static {
        $this->having[] = $sql;
        return $this;
    }

    public function orderBy(string|Expr|array $orderBy): static {
        if (is_array($orderBy)) {
            $this->orderBy = array_merge($this->orderBy, $orderBy);
        } else {
            $this->orderBy[] = $orderBy;
        }
        return $this;
    }

    public function limit(int $numOfRows, int $offset = null): static {
        $this->limit = [$offset, $numOfRows];
        return $this;
    }
}