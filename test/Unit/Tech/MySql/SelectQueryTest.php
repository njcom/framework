<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\MySql;

use Morpho\Tech\MySql\SelectQuery;
use Morpho\Tech\Sql\Expr;
use Morpho\Tech\Sql\IQuery;
use Morpho\Tech\Sql\ISelectQuery;
use UnexpectedValueException;

class SelectQueryTest extends QueryTest {
    use TUsingNorthwind;

    public function testInterface() {
        parent::testInterface();
        $this->assertInstanceOf(ISelectQuery::class, $this->query);
    }

    public static function dataColumns() {
        yield [
            'SELECT p.*',
            'p.*',
        ];
        yield [
            'SELECT `p`.*',
            ['p.*'],
        ];
        yield [
            "SELECT `p`.*, 'foo' AS type",
            ['p.*', new Expr("'foo' AS type")],
        ];
        yield [
            'SELECT `p`.*',
            ['p.*'],
        ];
        yield [
            "SELECT MICROSECOND('2019-12-31 23:59:59.000010'), NOW()",
            "MICROSECOND('2019-12-31 23:59:59.000010'), NOW()",
        ];
        yield [
            "SELECT MICROSECOND('2019-12-31 23:59:59.000010'), NOW()",
            new Expr("MICROSECOND('2019-12-31 23:59:59.000010'), NOW()"),
        ];
    }

    /**
     * @dataProvider dataColumns
     */
    public function testColumns(string $expected, $columns) {
        $this->assertSame($expected, (string) $this->query->columns($columns));
    }

    public function testColumns_ExprInTable() {
        $this->query->columns(['customers.id'])
            ->table(
                $this->query->expr('customers INNER JOIN orders ON customers.id = orders.customer_id')
            );
        $this->assertSqlEquals(
            'SELECT `customers`.`id` FROM customers INNER JOIN orders ON customers.id = orders.customer_id',
            $this->query->__toString()
        );
    }

    public function testOnlyTable() {
        $this->assertSqlEquals("SELECT * FROM `cars`", (string) $this->query->table('cars'));
    }

    public function testCompleteQuery() {
        $columns = 't.*, tL.startedAt, tL.endedAt, tL.exitCode';
        $join = 'taskLaunch tL ON t.id = tL.taskId';
        $numOfRows = 10;
        $offset = 5;
        $sql = (string) $this->query->table(['task' => 't'])
            ->columns($this->db->expr($columns))
            ->leftJoin($join)
            ->where(['t.id' => 123])
            ->groupBy('t.id')
            ->having($this->db->expr('MAX(tL.endedAt)'))
            ->orderBy('t.id')
            ->limit($numOfRows, $offset);
        $this->assertSqlEquals(
            "SELECT $columns
            FROM `task` AS `t`
            LEFT JOIN $join
            WHERE `t`.`id` = '123'
            GROUP BY `t`.`id`
            HAVING MAX(tL.endedAt)
            ORDER BY `t`.`id`
            LIMIT $offset, $numOfRows",
            $sql
        );
    }

    public function testOnlyOrderBy_ExprArg() {
        $this->assertSqlEquals(
            'SELECT * ORDER BY tL.endedAt DESC',
            $this->query->orderBy($this->db->expr('tL.endedAt DESC'))->sql()
        );
    }

    public static function dataJoin() {
        yield ['INNER'];
        yield ['LEFT'];
        yield ['RIGHT'];
    }

    /**
     * @dataProvider dataJoin
     */
    public function testJoin($joinType) {
        $columns = 'task AS t.*, tL.startedAt, tL.endedAt, tL.exitCode';
        $join = 'taskLaunch tL ON t.id = tL.taskId';
        $query = $this->query->table(['task', 'taskLaunch'])
            ->columns($this->query->expr($columns));
        $joinToMethod = [
            'INNER' => 'innerJoin',
            'LEFT'  => 'leftJoin',
            'RIGHT' => 'rightJoin',
        ];
        $method = $joinToMethod[$joinType];
        $sql = $query->$method($join)
            ->where(['foo' => 'bar'])
            ->__toString();
        $this->assertSqlEquals(
            "SELECT $columns FROM `task`, `taskLaunch` $joinType JOIN $join WHERE `foo` = 'bar'",
            $sql
        );
    }

    public function testWhereClause_OnlyCondition_ValidArg() {
        $this->assertSqlEquals(
            "SELECT * WHERE `foo` = 'abc' AND `bar` = 'efg'",
            $this->query->where(['foo' => 'abc', 'bar' => 'efg'])->__toString()
        );
    }

    public function testWhereClause_OnlyCondition_InvalidArg() {
        $this->expectException(UnexpectedValueException::class);
        $this->query->where(['foo', 'bar']);
    }

    public function testWhereClause_String() {
        $this->assertSqlEquals('SELECT * WHERE 1', $this->query->where('1')->__toString());
    }

    public function testTableRef() {
        $this->checkTableRef();
    }

    protected function mkQuery(): IQuery {
        return new SelectQuery($this->db);
    }
}
