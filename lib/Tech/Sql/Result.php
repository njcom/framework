<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Sql;

use Countable;
use PDO;
use PDOStatement;

class Result extends PDOStatement implements Countable {
    // Override the constructor to fix the "PDOException: SQLSTATE[HY000]: General error: user-supplied statement does not accept constructor arguments in ..."
    protected function __construct() {
    }

    /**
     * @param int $mode PDO::FETCH_ASSOC | PDO::FETCH_NUM | PDO::FETCH_BOTH | PDO::FETCH_NAMED
     * @return array
     */
    public function rows(int $mode = PDO::FETCH_ASSOC): array {
        return $this->fetchAll($mode);
    }

    /**
     * @param int $mode PDO::FETCH_ASSOC | PDO::FETCH_NUM | PDO::FETCH_BOTH | PDO::FETCH_NAMED
     * @return array|false
     */
    public function row(int $mode = PDO::FETCH_ASSOC): array|false {
        return $this->fetch($mode);
    }

    public function column(): array {
        return $this->fetchAll(PDO::FETCH_COLUMN);
    }

    public function bool(): bool {
        return (bool) $this->field();
    }

    /**
     * @return mixed|false Returns false if the value is not found, and other non-false scalar otherwise.
     */
    public function field(): mixed {
        return $this->fetchColumn(0);
    }

    public function dict(): array {
        return $this->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Has time complexity O(n)
     */
    public function count(): int {
        // @TODO: replace with iterator_count() ?
        $i = 0;
        foreach ($this as $_) {
            $i++;
        }
        return $i;
    }
}