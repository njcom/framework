<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\MySql;

use Morpho\Base\NotImplementedException;
use Morpho\Tech\Sql\IInsertQuery;

use function array_keys;
use function implode;

class InsertQuery extends Query implements IInsertQuery {
    protected array $rows = [];

    public function row(array $row): self {
        $this->rows[] = $row;
        //$args = \array_values($row);
        //return [$sql, $args];
        return $this;
    }

    public function args(): array {
        $args = [];
        foreach ($this->rows as $row) {
            $args = array_merge($args, array_values($row));
        }
        return $args;
    }

    /**
     * @param array{table: string, row: array} $spec
     * @return $this
     */
    public function build(array $spec): self {
        if (isset($spec['table'])) {
            $this->table($spec['table']);
        }
        if (isset($spec['row'])) {
            $this->row($spec['row']);
        }
        return $this;
    }

    public function lastId(): string {
        return $this->db->lastInsertId();
    }
    // todo: public funciton rows(array $rows): self {
    //}
    /*
        public function insertRows(string $tableName, array $rows, array $conf = null): void {
            $args = [];
            $keys = null;
            foreach ($rows as $row) {
                if (null === $keys) {
                    $keys = array_keys($row);
                }
                $args = array_merge($args, array_values($row));
            }
            $query = $this->query();
            $valuesClause = ', (' . implode(', ', $query->positionalPlaceholders($keys)) . ')';
            $sql = 'INSERT INTO ' . $query->quoteIdentifier($tableName) . ' (' . implode(', ', $query->quoteIdentifiers($keys)) . ') VALUES ' . ltrim(str_repeat($valuesClause, count($rows)), ', ');
            $this->eval($sql, $args);
        }
    
        abstract public function insertRows(string $tableName, array $rows): void;
    */
    public function sql(): string {
        if (count($this->rows) > 1) {
            throw new NotImplementedException("Inserting > 1 rows in one query is not implemented yet");
        }
        $row = $this->rows[0];
        // todo: support multiple rows
        return 'INSERT INTO ' . $this->db->quoteIdentifier($this->tables[0]) . ' (' . implode(
                ', ',
                $this->db->quoteIdentifier(array_keys($row))
            ) . ') VALUES (' . implode(', ', $this->db->positionalArgs($row)) . ')';
    }
}