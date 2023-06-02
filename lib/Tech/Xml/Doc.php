<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Xml;

use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use Morpho\Base\InvalidConfException;
use Morpho\Fs\File;
use RuntimeException;

use function array_diff_key;
use function call_user_func_array;
use function count;
use function is_file;
use function is_readable;
use function libxml_use_internal_errors;
use function Morpho\Base\e;
use function substr;
use function trim;

/**
 * @method XPathResult select(string $xPath, $contextNode = null)
 */
class Doc extends DOMDocument {
    const ENCODING = 'utf-8';
    /**
     * NB: true values are not actual values of the options.
     */
    private const CREATE_CONFIG_PARAMS = [
        'documentURI'         => true,
        'encoding'            => true,
        'formatOutput'        => true,
        'preserveWhiteSpace'  => true,
        'recover'             => true,
        'resolveExternals'    => true,
        'strictErrorChecking' => true,
        'substituteEntities'  => true,
        'validateOnParse'     => true,
        'xmlStandalone'       => true,
        'xmlVersion'          => true,
    ];
    private ?XPathQuery $xPath = null;

    public static function parseFile(string $filePath, array $conf = null): Doc {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new InvalidArgumentException("Unable to load DOM document from the file '$filePath'");
        }
        $source = File::read($filePath, ['removeBom' => true]);
        return self::parse($source, $conf);
    }

    public static function parse(string $source, array $conf = null): Doc {
        $source = trim($source);

        $conf = (array) $conf;
        $fixEncoding = $conf['fixEncoding'] ?? false;
        unset($conf['fixEncoding']);

        $doc = self::mk($conf);

        libxml_use_internal_errors(true);

        if (substr($source, 0, 5) == '<?xml') {
            $result = $doc->loadXML($source);
        } else {
            if ($fixEncoding) {
                $source = '<meta http-equiv="content-type" content="text/html; charset=' . e(
                        $conf['encoding'] ?? self::ENCODING
                    ) . '">'
                    . $source;
            }
            $result = $doc->loadHTML($source);
        }

        libxml_use_internal_errors(false);

        if (!$result) {
            throw new RuntimeException('Unable to load document.');
        }

        return $doc;
    }

    public static function mk(array $conf = null): Doc {
        $conf = (array) $conf;
        $invalidConf = array_diff_key($conf, self::CREATE_CONFIG_PARAMS);
        if (count($invalidConf)) {
            throw new InvalidConfException($invalidConf);
        }

        $doc = new Doc('1.0');
        $conf += [
            'preserveWhiteSpace' => false,
            'formatOutput'       => true,
            'substituteEntities' => true,
            'encoding'           => self::ENCODING,
        ];
        foreach ($conf as $name => $value) {
            $doc->$name = $value;
        }

        return $doc;
    }

    public function __call($method, $args): mixed {
        return call_user_func_array([$this->xPath(), $method], $args);
    }

    public function xPath(): XPathQuery {
        if (null === $this->xPath) {
            $this->xPath = new XPathQuery($this);
        }
        return $this->xPath;
    }

    public function namespaces(): iterable {
        $xpath = new DOMXPath($this);
        foreach ($xpath->query("namespace::*", $this->documentElement) as $node) {
            yield $node->localName => $node->nodeValue;
        }
    }
    /*
    public function addDomNode(DOMDocument $doc, $parentNode, $name, $value, array $attributes = array())
    {
      $node = $parentNode->appendChild($doc->createElement($name, htmlspecialchars($value, ENT_QUOTES)));
      foreach ($attributes as $name => $value) {
        $node->setAttribute($name, $value);
      }
    //  $element->appendChild($doc->createTextNode($value));
      return $node;
    }
    */
}
