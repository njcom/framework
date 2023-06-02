<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

readonly class Location {
    public int $lineNo;
    /**
     * Column number in characters.
     */
    public int $columnNo;
    /**
     * Offset in characters.
     */
    public ?int $offset;

    public function __construct(int $lineNo, int $columnNo, int $offset = null) {
        $this->lineNo = $lineNo;
        $this->columnNo = $columnNo;
        $this->offset = $offset;
    }
}