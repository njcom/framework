<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

class Location {
    public readonly int $lineNo;
    /**
     * Column number in characters.
     * @var int
     */
    public readonly int $columnNo;
    /**
     * Offset in characters.
     * @var int
     */
    public readonly int $offset;
    /**
     * Length in characters.
     * @var int
     */
    public readonly int $length;

    public function __construct(int $lineNo, int $columnNo, int $offset, int $length) {
        $this->lineNo = $lineNo;
        $this->columnNo = $columnNo;
        $this->offset = $offset;
        $this->length = $length;
    }
}