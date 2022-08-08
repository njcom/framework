<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */

namespace Morpho\Test\Unit\Tech\MySql;

use Morpho\Tech\MySql\DeleteQuery;
use Morpho\Tech\Sql\IDeleteQuery;
use Morpho\Tech\Sql\IQuery;
use PDO;

class DeleteQueryTest extends QueryTest {
    protected function setUp(): void {
        parent::setUp();
        $this->createCarsTable(true);
    }

    public function testInterface() {
        parent::testInterface();
        $this->assertInstanceOf(IDeleteQuery::class, $this->query);
    }

    public function testWithoutWhereClause() {
        $this->assertCount(3, $this->selectAllRows());

        $this->query->table('cars')->eval();

        $this->assertCount(0, $this->selectAllRows());
    }

    private function selectAllRows() {
        $stmt = $this->pdo->query('SELECT * FROM cars');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function testWithWhereClause() {
        $this->assertCount(3, $this->selectAllRows());

        $this->query->table('cars')->where(['color' => 'green'])->eval();

        $this->assertSame(
            [
                ['name' => 'Chevrolet Camaro', 'color' => 'red', 'country' => 'US', 'type1' => 1, 'type2' => 'US'],
            ],
            $this->selectAllRows()
        );
    }

    protected function mkQuery(): IQuery {
        return new DeleteQuery($this->db);
    }
}
