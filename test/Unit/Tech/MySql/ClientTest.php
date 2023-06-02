<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\MySql;

use Morpho\Tech\MySql\Client;
use Morpho\Tech\Sql\Expr;
use Morpho\Tech\Sql\IClient;
use Morpho\Tech\Sql\IQuery;
use Morpho\Tech\Sql\ISchema;
use Morpho\Tech\Sql\Result;
use PDOException;
use Throwable;

class ClientTest extends DbTestCase {
    public function testInterface() {
        $this->assertInstanceOf(IClient::class, $this->db);
    }

    public function testConnectDisconnect() {
        $dbConf = $this->dbConf();
        unset($dbConf['driver']);

        $dbClient = new Client($dbConf);

        $selectVersion = fn () => $dbClient->eval('SELECT VERSION()')->field();

        $this->assertTrue($dbClient->isConnected());
        $version = $selectVersion();
        $this->assertNotEmpty($version);

        $dbClient->disconnect();

        $this->assertFalse($dbClient->isConnected());

        try {
            $selectVersion();
        } catch (Throwable) {
        }

        $dbClient->connect();

        $this->assertTrue($dbClient->isConnected());
        $this->assertSame($version, $selectVersion());
    }

    public function testEval_ThrowsExceptionOnInvalidSql() {
        $this->expectException(PDOException::class, 'Column not found');
        $this->db->eval('SELECT invalid syntax');
    }

    public function testEval_NamedPlaceholders() {
        $modelName = "Chevrolet Camaro";
        $result = $this->db->eval(
            'SELECT * FROM cars WHERE color = :color AND name = :name',
            ['name' => $modelName, 'color' => 'red']
        );
        $this->assertInstanceOf(Result::class, $result);
        $rows = $result->rows();
        $this->assertSame(
            [['name' => $modelName, 'color' => 'red', 'country' => 'US', 'type1' => 1, 'type2' => 'US']],
            $rows
        );
    }

    public function testEval_PositionalPlaceholders() {
        $result = $this->db->eval('SELECT * FROM cars WHERE color = ?', ['red']);
        $this->assertInstanceOf(Result::class, $result);
        $rows = $result->rows();
        $this->assertSame(
            [['name' => "Chevrolet Camaro", 'color' => 'red', 'country' => 'US', 'type1' => 1, 'type2' => 'US']],
            $rows
        );
    }

    public function testExec() {
        $this->assertSame(3, $this->db->exec('DELETE FROM cars'));
    }

    public function testCanSwitchDb() {
        $dbConf = $this->dbConf();
        $curDbName = $this->db->dbName();

        $this->assertSame($dbConf['db'], $curDbName);
        $newDbName = 'mysql';
        $this->assertNotSame($newDbName, $curDbName);

        $this->assertSame($this->db, $this->db->useDb($newDbName));

        $this->assertSame($newDbName, $this->db->dbName());

        $this->assertSame($this->db, $this->db->useDb($curDbName));

        $this->assertSame($curDbName, $this->db->dbName());
    }

    public function testLastInsertId_NonAutoincrementCol() {
        $this->db->eval(
            <<<SQL
CREATE TABLE foo (
    bar varchar(255)
)
SQL
        );
        $this->db->eval('INSERT INTO foo VALUES (?)', ['test']);

        $this->assertEquals('0', $this->db->lastInsertId());
        $this->assertEquals('0', $this->db->lastInsertId('bar'));
    }

    public function testLastInsertId_AutoincrementCol() {
        $this->db->eval(
            <<<SQL
CREATE TABLE foo (
    baz int PRIMARY KEY AUTO_INCREMENT,
    bar varchar(255)
)
SQL
        );
        $this->db->eval('INSERT INTO foo (bar) VALUES (?)', ['test']);
        $this->assertEquals('1', $this->db->lastInsertId());
        $this->assertEquals('1', $this->db->lastInsertId('baz'));
    }

    public function testDriverName() {
        $driverName = $this->db->driverName();
        $this->assertNotEmpty($driverName);
        $this->assertEquals($this->dbConf()['driver'], $driverName);
    }

    public function testQueries() {
        foreach (['insert', 'select', 'update', 'delete', 'replace'] as $method) {
            $query = $this->db->$method();
            $this->assertInstanceOf(IQuery::class, $query);
            $this->assertNotSame($query, $this->db->$method());
        }
    }

    public function testQuoteIdentifer() {
        // Scalars
        $this->assertSame('`foo`.`bar`', $this->db->quoteIdentifier('foo.bar'));
        $this->assertSame('`foo`', $this->db->quoteIdentifier('foo'));
        $this->assertSame('foo', $this->db->quoteIdentifier(new Expr('foo')));
        // Arrays
        $this->assertSame(['`foo`.`bar`'], $this->db->quoteIdentifier(['foo.bar']));
        $this->assertSame(['`foo`'], $this->db->quoteIdentifier(['foo']));
        $this->assertSame(['foo', 'bar'], $this->db->quoteIdentifier([new Expr('foo'), new Expr('bar')]));
    }

    public function testQuoteIdentifierStr() {
        // Scalars
        $this->assertSame('`foo`.`bar`', $this->db->quoteIdentifierStr('foo.bar'));
        $this->assertSame('`foo`', $this->db->quoteIdentifierStr('foo'));
        $this->assertSame('foo', $this->db->quoteIdentifierStr(new Expr('foo')));

        // Arrays
        $this->assertSame('`foo`.`bar`', $this->db->quoteIdentifierStr(['foo.bar']));
        $this->assertSame('`foo`', $this->db->quoteIdentifierStr(['foo']));
        $this->assertSame('`foo`.`bar`, `test`', $this->db->quoteIdentifierStr(['foo.bar', 'test']));
        $this->assertSame('foo, bar', $this->db->quoteIdentifierStr([new Expr('foo'), new Expr('bar')]));
    }

    public function testPositionalArgs() {
        $this->assertSame([], $this->db->positionalArgs([]));
        $this->assertSame(['?', '?'], $this->db->positionalArgs(['foo', 'bar']));
        $this->assertSame(['?', '?'], $this->db->positionalArgs(['foo' => 123, 'bar' => 456]));
    }

    public function testNameValArgs() {
        $this->assertSame([], $this->db->positionalArgs([]));
        $this->assertSame(['`foo` = ?', '`bar` = ?'], $this->db->nameValArgs(['foo' => 123, 'bar' => 456]));
    }

    public function testSchema() {
        $this->assertInstanceOf(ISchema::class, $this->db->schema());
    }

    public function testWhere() {
        $where = $this->db->where('1');
        $this->assertSame('WHERE 1', $where[0]);
        $this->assertSame([], $where[1]);

        $where = $this->db->where(['foo' => 'bar']);
        $this->assertSame('WHERE `foo` = ?', $where[0]);
        $this->assertSame(['bar'], $where[1]);
    }

    public function testTransaction() {
        $fn = function () {
            return func_get_args();
        };
        $ret = $this->db->transaction($fn, 'test', 'bar');
        $this->assertSame([$this->db, 'test', 'bar'], $ret);
    }

    protected function createFixtures($db): void {
        $this->createCarsTable(true);
    }
}
