<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\DataProcessing;

use Morpho\Base\IHasServiceManager;
use Morpho\Base\IServiceManager;
use Morpho\Tech\Sql\IClient;

use function intval;

abstract class DbPager extends Pager implements IHasServiceManager {
    protected IServiceManager $serviceManager;
    protected ?IClient $db;

    public function setServiceManager(IServiceManager $serviceManager): static {
        $this->serviceManager = $serviceManager;
        $this->db = null;
        return $this;
    }

    protected function itemList($offset, $pageSize): iterable {
        $offset = intval($offset);
        $pageSize = intval($pageSize);
        return $this->db()->eval('SELECT * FROM (' . $this->sqlQuery() . ") AS t LIMIT {$offset}, {$pageSize}");
    }

    protected function db() {
        if (null === $this->db) {
            return $this->serviceManager['db'];
        }
        return $this->db;
    }

    protected abstract function sqlQuery();

    protected function calculateTotalItemsCount(): int {
        return $this->db()->eval('SELECT COUNT(*) FROM (' . $this->sqlQuery() . ') AS t')->field();
    }
}