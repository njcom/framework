<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */

namespace Morpho\Test\Unit\Tech\MySql;

use Morpho\Tech\MySql\ReplaceQuery;
use Morpho\Tech\Sql\IQuery;
use Morpho\Tech\Sql\IReplaceQuery;

class ReplaceQueryTest extends QueryTest {
    public function testInterface() {
        parent::testInterface();
        $this->assertInstanceOf(IReplaceQuery::class, $this->query);
    }

    public function testQuery() {
        $this->markTestIncomplete();
    }

    protected function mkQuery(): IQuery {
        return new ReplaceQuery($this->db);
    }
}
