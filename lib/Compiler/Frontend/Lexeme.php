<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

/**
 * https://en.wikipedia.org/wiki/Punctuation
 */
enum Lexeme {
    // Double quote
    public const DoubleQ = '"';
    // Back quote
    public const BackQ = '`';
    // Apostrophe
    public const Apos = "'";
    // Opening parenthesis
    public const OpenParen = '(';
    // Closing parenthesis
    public const CloseParen = ')';
    // Opening square bracket
    public const OpenBracket = '[';
    // Closing square bracket
    public const CloseBracket = ']';
    // Opening curly brace
    public const OpenBrace = '{';
    // Closing curly brace
    public const CloseBrace = '}';
    // Opening angle bracket
    public const OpenAngleBracket = '<';
    // Closing angle bracket
    public const CloseAngleBracket = '>';
    public const Comma = ',';
    public const Dot = '.';
    public const Hyphen = '-';
    public const Semicolon = ';';
}