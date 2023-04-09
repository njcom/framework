<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\MySql;

//use Morpho\Base\Arr;
use Morpho\Tech\MySql\Schema;
use Morpho\Tech\Sql\ISchema;
use PDO;

use function md5;

//use const Morpho\Base\INT_TYPE;
//use const Morpho\Base\STRING_TYPE;

class SchemaTest extends DbTestCase {
    protected ISchema $schema;

    private array $dbs = [];

    protected function setUp(): void {
        parent::setUp();
        $this->schema = new Schema($this->db);
        $this->dbs = [];
    }

    protected function tearDown(): void {
        parent::tearDown();
        foreach ($this->dbs as $dbName) {
            $this->db->eval("DROP DATABASE IF EXISTS " . $dbName);
        }
    }

    public function testDbNames() {
        $dbSuffix = md5(__FUNCTION__);
        $dbName = 't' . $dbSuffix;

        $this->assertContains('mysql', $this->schema->dbNames());
        $this->assertNotContains($dbName, $this->schema->dbNames());

        $this->createDb($dbName);

        $this->assertContains($dbName, $this->schema->dbNames());
        $this->assertContains('mysql', $this->schema->dbNames());
    }

    private function createDb($dbName, string $charset = null, string $collation = null): string {
        $this->dbs[] = $dbName;
        $this->db->eval(
            "CREATE DATABASE $dbName CHARACTER SET " . ($charset ?: Schema::CHARSET) . " COLLATE " . ($collation ?: Schema::COLLATION)
        );
        return $dbName;
    }

    public function testDbExists() {
        $dbSuffix = md5(__FUNCTION__);
        $dbName = 't' . $dbSuffix;
        $this->assertFalse($this->schema->dbExists($dbName));
        $this->createDb($dbName);
        $this->assertTrue($this->schema->dbExists($dbName));
    }

    public function testTableNamesAndTableExists() {
        $this->assertEmpty($this->schema->tableNames());
        $this->assertFalse($this->schema->tableExists('cars'));
        $this->createCarsTable(false);
        $this->assertSame(['cars'], $this->schema->tableNames());
        $this->assertTrue($this->schema->tableExists('cars'));
    }

    /*
        public function testTableDefinitionForNonExistingTable() {
            $this->expectException('\RuntimeException', "The table 'foo' does not exist");
            $this->schema->tableDefinition('foo');
        }

        public function testRenameColumn() {
            $this->markTestIncomplete();
        }

        public function testRenameTable() {
            $this->markTestIncomplete();
        }

        public function testSizeOfDatabases() {
            $this->markTestIncomplete();
        }

        public function testSizeOfDatabase() {
            $size = $this->schema->sizeOfDatabase('mysql');
            $this->assertGreaterThan(0, $size);
            $sum = 0;
            foreach ($this->db->eval("SELECT DATA_LENGTH, INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'mysql'") as $row) {
                $sum += $row['DATA_LENGTH'] + $row['INDEX_LENGTH'];
            }
            $this->assertSame($sum, $size);
        }

        public function testSizeOfTables() {
            $size = $this->schema->sizeOfTables('mysql');
            $this->assertIsArray($size);
            $this->assertNotEmpty($size);
            $expectedKeys = ['tableName', 'tableType', 'sizeInBytes'];
            foreach ($size as $row) {
                $this->assertArrayHasOnlyItemsWithKeys($expectedKeys, $row);
            }
        }

        public function testSizeOfTable() {
            $this->markTestIncomplete();
        }

        public function testAvailableCharsetsOfServer() {
            $checkVal = function ($val, $expectedType) {
                $this->assertNotEmpty($val);
                switch ($expectedType) {
                    case STRING_TYPE:
                        $this->assertIsString($val);
                        break;
                    case INT_TYPE:
                        $this->assertIsInt($val);
                        break;
                    default:
                        throw new \UnexpectedValueException();
                }
            };
            $i = 0;
            foreach ($this->schema->availableCharsetsOfServer() as $charset) {
                /** @var ServerCharset $charset *  /
                $this->assertInstanceOf(ServerCharset::class, $charset);
                $checkVal($charset->name(), STRING_TYPE);
                $checkVal($charset->description(), STRING_TYPE);
                $checkVal($charset->charSize(), INT_TYPE);
                $collation = $charset->defaultCollation();
                $this->assertInstanceOf(ServerCollation::class, $collation);
                $this->assertNotEmpty($collation->name());
                $this->assertSame($charset->name(), $collation->charsetName());
                $this->assertTrue($collation->isDefault());
                $i++;
            }
            $this->assertTrue($i > 0);
        }

        public static function dataAvailableCollationsOfServer() {
            yield [null, function ($defaultEncountered) {
                $this->assertTrue($defaultEncountered > 0);
            }];
            yield ["WHERE Charset = 'utf8'", function ($defaultEncountered) {
                $this->assertSame(1, $defaultEncountered);
            }];
        }

        /**
         * @dataProvider dataAvailableCollationsOfServer
         * /
        public function testAvailableCollationsOfServer(?string $constraint, callable $checkDefaultEncountered) {
            $collations = $this->schema->availableCollationsOfServer($constraint);
            $i = $defaultEncountered = 0;
            foreach ($collations as $collation) {
                /** @var ServerCollation $collation * /
                if ($collation->isDefault()) {
                    $defaultEncountered++;
                }
                $i++;
                $this->assertNotEmpty($collation->name());
                $this->assertNotEmpty($collation->charsetName());
            }
            $checkDefaultEncountered($defaultEncountered);
            $this->assertTrue($i > 0);
        }

        public function testVarsWithPrefix_NormalCase() {
            $prefix = 'character_set';
            $vars = $this->schema->varsWithPrefix($prefix);
            foreach ($vars as $key => $value) {
                $this->assertStringStartsWith($prefix, $key);
            }
            $this->assertTrue(\count($vars) > 0);
        }

        public function testVarsWithPrefix_HandlesPercent() {
            $this->assertSame([], $this->schema->varsWithPrefix('%'));
        }

        public function testVarsWithSuffix_NormalCase() {
            $suffix = '_size';
            $vars = $this->schema->varsWithSuffix($suffix);
            foreach ($vars as $key => $value) {
                $this->assertStringEndsWith($suffix, $key);
            }
            $this->assertTrue(\count($vars) > 0);
        }

        public function testVarsWithSuffix_HandlesPercent() {
            $this->assertSame([], $this->schema->varsWithSuffix($this->db->quote('%')));
        }

        public function testVarsLike_NormalCase() {
            $infix = 'character';
            $vars = $this->schema->varsLike($infix);
            foreach ($vars as $key => $value) {
                $this->assertStringContainsString($infix, $key);
            }
            $this->assertTrue(\count($vars) > 0);
        }

        public function testVarsLike_HandlesPercent() {
            $this->assertSame([], $this->schema->varsLike('%'));
        }

        public function testCharsetAndCollationVars() {
            $vars = $this->schema->charsetAndCollationVars();
            $this->assertTrue(\count($vars) > 0);
            foreach ($vars as $key => $value) {
                $this->assertTrue(0 === \strpos($key, 'collation') || 0 === strpos($key, 'character_set'));
            }
        }

        public function testCollationOfServer() {
            $collation = $this->schema->collationOfServer();
            $this->assertInstanceOf(ServerCollation::class, $collation);
            $this->assertNotEmpty($collation->name());
            $this->assertNotEmpty($collation->charsetName());
        }

        public function testCollationOfDatabase() {
            $charset = 'gb2312';
            $collationName = $charset . '_bin';
            $dbName = $this->callCreateDatabase('t' . \md5(__FUNCTION__), $charset, $collationName);

            $collation = $this->schema->collationOfDatabase($dbName);
            $this->assertInstanceOf(DbCollation::class, $collation);
            $this->assertSame($dbName, $collation->dbName());
            $this->assertSame($collationName, $collation->name());
            $this->assertSame($charset, $collation->charsetName());
        }

        public function testCollationOfTables() {
            $this->createTestTables();
            $dbName = self::DB;
            $collations = $this->schema->collationOfTables($dbName);
            $i = 0;
            foreach ($collations as $collation) {
                $i++;
                /** @var TableCollation $collation * /
                $this->assertInstanceOf(TableCollation::class, $collation);
                $this->assertSame($dbName, $collation->dbName());
                switch ($collation->tableName()) {
                    case'cherry':
                        $this->assertSame('gb2312', $collation->charsetName());
                        $this->assertSame('gb2312_bin', $collation->name());
                        break;
                    case 'kiwi';
                        $this->assertSame('cp1250', $collation->charsetName());
                        $this->assertSame('cp1250_croatian_ci', $collation->name());
                        break;
                    default:
                        $this->fail();
                }
            }
            $this->assertTrue($i > 0);
        }

        public static function dataCollationOfTable() {
            yield [self::DB . '.kiwi', null];
            yield [self::DB, 'kiwi'];
        }

        /**
         * @dataProvider dataCollationOfTable
         * /
        public function testCollationOfTable(string $dbName, ?string $tableName) {
            $this->createTestTables();

            $collation = $this->schema->collationOfTable($dbName, $tableName);

            $this->assertInstanceOf(TableCollation::class, $collation);
            $this->assertSame('kiwi', $collation->tableName());
            $this->assertSame(self::DB, $collation->dbName());
            $this->assertSame('cp1250', $collation->charsetName());
            $this->assertSame('cp1250_croatian_ci', $collation->name());
        }

        public function testCollationOfColumns() {
            $this->markTestIncomplete();
        }

        public function testOptionsForCreateTableStmt() {
            $this->assertSame('ENGINE=InnoDB DEFAULT CHARSET=utf8', $this->schema->optionsForCreateTableStmt());
        }

        private function assertArrayHasOnlyItemsWithKeys(array $expectedKeys, array $arr) {
            $this->assertTrue(
                Arr::setsEqual($expectedKeys, \array_keys($arr)),
                \print_r($expectedKeys, true) . \print_r(\array_keys($arr), true)
            );
        }
    */

    public function testDeleteAllTables() {
        $this->createCarsTable(false);
        $tableNames = fn () => $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('cars', $tableNames());
        $this->schema->deleteAllTables();
        $this->assertSame([], $tableNames());
    }
    /*
        private function createTestTables(): void {
            $this->db->eval("CREATE TABLE cherry (id int) CHARACTER SET gb2312 COLLATE gb2312_bin");
            $this->db->eval("CREATE TABLE kiwi (id int) CHARACTER SET cp1250 COLLATE cp1250_croatian_ci");
        }
    */
}
