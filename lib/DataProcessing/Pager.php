<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\DataProcessing;

use Countable;
use Iterator;

use function array_slice;
use function ceil;
use function count;
use function intval;
use function max;

class Pager implements Iterator, Countable {
    protected $items = [];
    protected int $pageSize = 20;
    protected int $currentPageNumber = 1;
    private bool $isValid = false;
    private ?int $totalItemsCount = null;

    public function setItems(array $items): void {
        $this->items = $items;
        $this->totalItemsCount = null;
    }

    public function setPageSize(int $pageSize): void {
        $this->pageSize = max(intval($pageSize), 1);
        $this->totalItemsCount = null;
    }

    public function currentPage(): iterable {
        return $this->mkPageByNumber($this->currentPageNumber());
    }

    public function mkPageByNumber(int $pageNumber): Page {
        $pageNumber = max(intval($pageNumber), 1);
        $pageSize = $this->pageSize();
        $offset = ($pageNumber - 1) * $pageSize;
        return $this->mkPage($this->items($offset, $pageSize));
    }

    public function pageSize(): int {
        return $this->pageSize;
    }

    /**
     * Creates a new Page with $items.
     */
    protected function mkPage(array $items): Page {
        return new Page($items);
    }

    protected function items(int $offset, int $pageSize): array {
        return array_slice($this->items, $offset, $pageSize);
    }

    public function currentPageNumber(): int {
        return $this->currentPageNumber;
    }

    public function rewind(): void {
        $this->isValid = true;
        $this->setCurrentPageNumber(1);
    }

    public function setCurrentPageNumber(int $pageNumber): void {
        $pageNumber = intval($pageNumber);
        $totalPagesCount = $this->totalPagesCount();
        if ($pageNumber > $totalPagesCount) {
            $pageNumber = $totalPagesCount;
        } elseif ($pageNumber < 1) {
            $pageNumber = 1;
        }
        $this->currentPageNumber = $pageNumber;
    }

    public function totalPagesCount(): int {
        return (int) ceil($this->totalItemsCount() / $this->pageSize());
    }

    public function totalItemsCount(): int {
        if (null === $this->totalItemsCount) {
            $this->totalItemsCount = $this->calculateTotalItemsCount();
        }
        return $this->totalItemsCount;
    }

    protected function calculateTotalItemsCount(): int {
        return count($this->items);
    }

    public function current(): Page {
        return $this->mkPageByNumber($this->currentPageNumber());
    }

    public function valid(): bool {
        return $this->isValid && $this->currentPageNumber() <= $this->totalPagesCount();
    }

    public function key(): string|int {
        return $this->currentPageNumber();
    }

    public function next(): void {
        $nextPageNumber = $this->currentPageNumber() + 1;
        if ($nextPageNumber > $this->totalPagesCount()) {
            $this->isValid = false;
        } else {
            $this->setCurrentPageNumber($this->currentPageNumber() + 1);
        }
    }

    public function count(): int {
        return $this->totalPagesCount();
    }
}