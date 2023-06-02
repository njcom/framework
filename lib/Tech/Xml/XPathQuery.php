<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Xml;

use DOMDocument;
use DOMNodeList;
use DOMXPath;
use Morpho\Base\NotImplementedException;
use RuntimeException;

use function call_user_func_array;

class XPathQuery {
    private $xPath;

    public function __construct(DOMDocument $doc) {
        $this->xPath = new DOMXPath($doc);
    }

    public static function formFields(): string {
        // @TODO
        return 'input|textarea|option[@name]|';
    }

    public function select(string $xPath, $contextNode = null): XPathResult {
        $nodeList = $this->eval($xPath, $contextNode);
        if (!$nodeList instanceof DOMNodeList) {
            throw new RuntimeException('Unable to select DOMNodeList, consider to use the xPath() method instead.');
        }
        return new XPathResult($nodeList);
    }

    /**
     * @return @TODO
     */
    public function eval(string $xPath, $contextNode = null) {
        if (null !== $contextNode) {
            $result = $this->xPath->evaluate($xPath, $contextNode);
        } else {
            $result = $this->xPath->evaluate($xPath);
        }
        if (false === $result) {
            throw new RuntimeException("The XPath expression '$xPath' is not valid.");
        }
        return $result;
    }

    public function xPathString($node) {
        throw new NotImplementedException();
        /*
        @TODO
        if ($node instanceof SimpleXMLElement) {
                $node = dom_import_simplexml($node);
        } elseif (!$node instanceof DOMNode) {
                die('Not a node?');
        }

        $q         = new DOMXPath($node->ownerDocument);
        $xpath = '';

        do {
                $position = 1 + $q->query('preceding-sibling::*[name()="' . $node->nodeName . '"]', $node)->length;
                $xpath        = '/' . $node->nodeName . '[' . $position . ']' . $xpath;
                $node         = $node->parentNode;
        } while (!$node instanceof DOMDocument);

        return $xpath;
        */
    }

    public function position($xPath) {
        return $this->eval("count($xPath/preceding-sibling::*)+1");
    }

    public function __call($method, $args) {
        return call_user_func_array([$this->xPath, $method], $args);
    }
}
