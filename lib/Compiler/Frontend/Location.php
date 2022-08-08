<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

class Location {
    public readonly int $lineNo;
    public readonly int $columnNo;
    public readonly string $filePath;

    public function __construct(int $lineNo, int $columnNo, string $filePath) {
        $this->lineNo = $lineNo;
        $this->columnNo = $columnNo;
        $this->filePath = $filePath;
    }
}