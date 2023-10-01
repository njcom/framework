<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

use RuntimeException;

/**
 * Based on Python's SyntaxError (Include/cpython/pyerrors.h)
 */
class SyntaxError extends RuntimeException {
    public function __construct(string $msg, string $filePath, array $start, array $end, string $text, /*BorrPyObject *print_file_and_line;*/) {
        $formattedText = $text;
        if (!str_ends_with($text, "\n")) {
            $formattedText .= "\n";
        }
        $formattedText .= str_repeat(' ', $start[1]) . "^";
        parent::__construct('File: ' . $filePath . "\nLine: " . $start[0] . "\nColumn: " . $start[1] . "\n$formattedText\nError: " . $msg);
    }
}