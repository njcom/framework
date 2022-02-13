<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Xml;

use Countable;
use DOMNode;
use DOMNodeList;
use Iterator;

class XPathResult implements Iterator, Countable {
    protected DOMNodeList $nodeList;

    protected int $offset = 0;

    public function __construct(DOMNodeList $nodeList) {
        $this->nodeList = $nodeList;
    }

    public function toHtml(array $conf = null): string {
        $doc = Doc::mk($conf);
        foreach ($this->nodeList as $node) {
            $doc->appendChild($doc->importNode($node, true));
        }
        return $doc->saveHTML();
    }

    public function head(): ?DOMNode {
        return $this->nodeList->item(0);
    }

    public function tail(): array {
        $first = false;
        $res = [];
        foreach ($this->nodeList as $node) {
            if ($first) {
                $res[] = $node;
            } else {
                $first = true;
            }
        }
        return $res;
    }

    public function last(): ?DOMNode {
        return $this->item($this->count() - 1);
    }

    public function item(int $offset): ?DOMNode {
        return $this->nodeList->item($offset);
    }

    public function count(): int {
        return $this->nodeList->length;
    }

    public function init(): array {
        $stop = $this->count() - 1;
        $res = [];
        foreach ($this->nodeList as $i => $node) {
            if ($i === $stop) {
                return $res;
            }
            $res[] = $node;
        }
        return $res;
    }

    public function current(): ?DOMNode {
        return $this->item($this->offset);
    }

    public function next(): void {
        $this->offset++;
    }

    public function key(): int {
        return $this->offset;
    }

    public function rewind(): void {
        $this->offset = 0;
    }

    public function valid(): bool {
        return (bool) $this->item($this->offset);
    }
}
