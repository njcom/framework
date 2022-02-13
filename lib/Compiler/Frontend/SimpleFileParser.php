<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

abstract class SimpleFileParser implements ISimpleFileParser {
    public function parseFile(string $filePath): mixed {
        $fileNode = $this->mkFileNode();
        $fileNode->setPath($filePath);
        $fileNode->append($this->parseStr(file_get_contents($filePath)));
        return $fileNode;
    }
}