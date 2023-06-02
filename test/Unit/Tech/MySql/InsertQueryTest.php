<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\MySql;

use Morpho\Tech\MySql\InsertQuery;
use Morpho\Tech\Sql\IInsertQuery;
use Morpho\Tech\Sql\IQuery;
use Morpho\Tech\Sql\Result;
use PDO;

class InsertQueryTest extends QueryTest {
    public function testInterface() {
        parent::testInterface();
        $this->assertInstanceOf(IInsertQuery::class, $this->query);
    }

    public function testBuild() {
        $sql = $this->query->build(['table' => 'pkg', 'row' => ['name' => 'foo']])->__toString();
        $this->assertSame("INSERT INTO `pkg` (`name`) VALUES ('foo')", $sql);
    }

    public function testQuery() {
        $insert = new InsertQuery($this->db);

        $selectAllRows = fn () => $this->pdo->query('SELECT * FROM cars')->fetchAll(PDO::FETCH_ASSOC);
        $this->assertSame([], $selectAllRows());

        $insert = $insert->table('cars')->row(['color' => 'green', 'name' => 'Honda']);
        $result = $insert->eval();
        $this->assertInstanceOf(Result::class, $result);


        $this->assertSame(
            [
                [
                    'name'    => 'Honda',
                    'color'   => 'green',
                    'country' => null,
                    'type1'   => null,
                    'type2'   => null,
                ],
            ],
            $selectAllRows()
        );
    }

    protected function createFixtures($db): void {
        $this->createCarsTable(false);
    }

    protected function mkQuery(): IQuery {
        return new InsertQuery($this->db);
    }
}
