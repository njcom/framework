<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\MySql;

use Morpho\Tech\Sql\IReplaceQuery;

class ReplaceQuery extends Query implements IReplaceQuery {
    /*    public function build(): array {
              throw new NotImplementedException();
          }*/
    public function sql(): string {
        // TODO: Implement sql() method.
    }

    public function args(): array {
        // TODO: Implement args() method.
    }
}