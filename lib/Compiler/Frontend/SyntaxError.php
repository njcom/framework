<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

use RuntimeException;

/**
 * Based on Python's SyntaxError (Include/cpython/pyerrors.h)
 */
class SyntaxError extends RuntimeException {
    public function __construct(string $msg, string $filePath, Location $start, Location $end, string $text, /*BorrPyObject *print_file_and_line;*/) {
        $formattedText = $text;
        if (!str_ends_with($text, "\n")) {
            $formattedText .= "\n";
        }
        $formattedText .= str_repeat(' ', $start->columnNo) . "^";
        parent::__construct('File: ' . $filePath . "\nLine: " . $start->lineNo . "\nColumn: " . $start->columnNo . "\n$formattedText\nError: " . $msg);
    }
}