<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use ArrayAccess;
use IteratorAggregate;
use RuntimeException;
use Traversable;

use function array_keys;

/**
 * Index of all known modules.
 */
class ModuleIndex implements IteratorAggregate {
    private array|ArrayAccess|null $index = null;
    private IModuleIndexer $indexer;
    private ?array $loaded = [];

    public function __construct(IModuleIndexer $indexer) {
        $this->indexer = $indexer;
    }

    public function moduleNames(): iterable {
        $this->init();
        return array_keys($this->index);
    }

    private function init(): void {
        if (null === $this->index) {
            $this->index = $this->indexer->index();
        }
    }

    public function moduleExists(string $moduleName): bool {
        $this->init();
        return isset($this->index[$moduleName]);
    }

    public function module(string $moduleName): Module {
        $this->init();
        if (!isset($this->index[$moduleName])) {
            throw new RuntimeException("The module '$moduleName' was not found in index");
        }
        if (isset($this->loaded[$moduleName])) {
            return $this->loaded[$moduleName];
        }
        return $this->loaded[$moduleName] = $this->mkModule($moduleName, $this->index[$moduleName]);
    }

    protected function mkModule(string $moduleName, $meta): Module {
        return new BackendModule($moduleName, $meta);
    }

    public function rebuild(): void {
        $this->index = $this->loaded = null;
        $this->indexer->clear();
    }
    public function getIterator(): Traversable {

        $this->init();
        foreach ($this->index as $moduleName => $_) {
            yield $moduleName;
        }
    }
}
