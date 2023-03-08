<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Sqlite;

use Morpho\Base\NotImplementedException;
use Morpho\Tech\Sql\Client as BaseClient;
use Morpho\Tech\Sql\IDeleteQuery;
use Morpho\Tech\Sql\IInsertQuery;
use Morpho\Tech\Sql\IReplaceQuery;
use Morpho\Tech\Sql\ISchema;
use Morpho\Tech\Sql\ISelectQuery;
use Morpho\Tech\Sql\IUpdateQuery;

class Client extends BaseClient {
    protected function checkConf(array $conf): array {
        throw new NotImplementedException();
    }

    public function connect(): void {
        throw new NotImplementedException();
    }

    public function insert(array $spec = null): IInsertQuery {
        throw new NotImplementedException();
    }

    public function select(array $spec = null): ISelectQuery {
        throw new NotImplementedException();
    }

    public function update(array $spec = null): IUpdateQuery {
        throw new NotImplementedException();
    }

    public function delete(array $spec = null): IDeleteQuery {
        throw new NotImplementedException();
    }

    public function replace(array $spec = null): IReplaceQuery {
        throw new NotImplementedException();
    }

    public function schema(): ISchema {
        throw new NotImplementedException();
    }

    public function dbName(): ?string {
        throw new NotImplementedException();
    }

    public function useDb(string $dbName): static {
        throw new NotImplementedException();
    }
}