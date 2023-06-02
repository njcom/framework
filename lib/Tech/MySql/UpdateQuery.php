<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\MySql;

use Morpho\Tech\Sql\IUpdateQuery;

class UpdateQuery extends Query implements IUpdateQuery {
    protected array $tables = [];
    protected array $columns = [];

    public function columns(array|string $columns): static {
        if (is_array($columns)) {
            $this->columns = array_merge($this->columns, $columns);
        } else {
            $this->columns[] = $columns;
        }
        return $this;
    }

    public function sql(): string {
        /*
                UPDATE [LOW_PRIORITY] [IGNORE] table_reference
                [PARTITION (partition_list)]
          SET col1={expr1|DEFAULT} [,col2={expr2|DEFAULT}] ...
          [WHERE where_condition]
        
            // todo
          [ORDER BY ...]
          [LIMIT row_count]
        */
        $sql = ['UPDATE', $this->tableRefStr()];
        $sql[] = 'SET';
        $sql[] = implode(', ', $this->db->nameValArgs($this->columns));
        if ($this->where) {
            $sql[] = $this->whereStr();
        }
        return implode("\n", $sql);
    }

    public function args(): array {
        return array_merge(array_values($this->columns), $this->args);
    }
}