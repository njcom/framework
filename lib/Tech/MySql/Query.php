<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\MySql;

use Morpho\Base\NotImplementedException;
use Morpho\Tech\Sql\Expr;
use Morpho\Tech\Sql\IDbClient;
use Morpho\Tech\Sql\IQuery;
use Morpho\Tech\Sql\Result;
use Stringable;

abstract class Query implements IQuery {
    protected IDbClient $db;
    protected array $tables = [];
    protected array $where = [];
    protected array $args = [];

    public function __construct(IDbClient $db, array $spec = null) {
        $this->db = $db;
        if (null !== $spec) {
            $this->build($spec);
        }
    }

    public function build(array $spec): static {
        throw new NotImplementedException(__METHOD__);
    }

    /**
     * @param string|array|Expr $tableName For alias use: ['myTableName' => 'myAlias']
     * @return $this
     */
    public function table(string|array|Expr $tableName): static {
        $this->tables[] = $tableName;
        return $this;
    }

    public function expr(mixed $expr): Expr {
        return $this->db->expr($expr);
    }

    public function where(array|Stringable|string $condition, $args = null): static {
        if (null === $args) {
            // $args not specified => $condition contains arguments
            if (is_array($condition)) {
                $this->where[] = implode(' AND ', $this->db->nameValArgs($condition));
                $this->args = array_merge($this->args, array_values($condition));
            } else {
                $this->where[] = (string) $condition;
            }
        } else {
            $this->where[] = $condition;
            $this->args = array_merge($this->args, (array) $args);
        }
        return $this;
    }

    public function eval(): Result {
        return $this->db->eval($this->sql(), $this->args());
    }

    public abstract function sql(): string;

    public function args(): array {
        return $this->args;
    }

    /**
     * This method is for debugging only and should not be run on the server as SQL injection is possible, use eval() instead.
     * It is useful to see how the full SQL query will look like with placeholders substituded with arguments.
     * @return string
     */
    public function __toString(): string {
        $sql = $this->sql();
        $args = $this->args();
        if ($args) {
            // todo: replace named args like foo = :foo AND bar = :bar
            $sql = preg_replace_callback(
                '~\\?~s',
                function ($match) use (&$args): string {
                    $val = array_shift($args);
                    return $this->db->quote((string) $val); // NB: this may be not safe due SQL-injection.
                },
                $sql
            );
        }
        return $sql;
    }

    protected function tableRefStr(): string {
        // https://mariadb.com/kb/en/join-syntax/
        $sql = [];
        foreach ($this->tables as $table) {
            if ($table instanceof Expr) {
                $sql[] = $table->val();
            } elseif (is_array($table)) {
                $tables = [];
                foreach ($table as $name => $alias) {
                    if (is_int($name) || preg_match('~^\\d+$~', $name)) {
                        $tables[] = $this->db->quoteIdentifier($alias);
                        // alias is table name
                    } else {
                        $tables[] = $this->db->quoteIdentifier($name) . ' AS ' . $this->db->quoteIdentifier($alias);
                    }
                }
                $sql[] = implode(', ', $tables);
            } else {
                $sql[] = $this->db->quoteIdentifier($table);
            }
        }
        return implode(', ', $sql);
    }

    protected function whereStr(): string {
        return 'WHERE ' . implode(' AND ', $this->where);
    }
}