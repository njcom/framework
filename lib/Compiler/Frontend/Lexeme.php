<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

enum Lexeme: string {
    case At = '@';
    // Back quote
    case BackQuote = '`';
    case BackSlash = '\\';
    case Caret = '^';
    // Closing angle bracket
    case CloseAngleBracket = '>';
    // Closing curly brace
    case CloseBrace = '}';
    // Closing square bracket
    case CloseBracket = ']';
    // Closing parenthesis
    case CloseParen = ')';
    case Comma = ',';
    case Colon = ':';
    case Dollar = '$';
    case Dot = '.';
    // Double quote
    case DoubleQuote = '"';
    case Equal = '=';
    case Exclamation = '!';
    case Hash = '#';
    // Aka Dash/Hyphen
    case Minus = '-';
    // Opening angle bracket
    case OpenAngleBracket = '<';
    // Opening curly brace
    case OpenBrace = '{';
    // Opening square bracket
    case OpenBracket = '[';
    // Opening parenthesis
    case OpenParen = '(';
    case Question = '?';
    case Percent = '%';
    case Plus = '+';
    case Semicolon = ';';
    // Aka Apostrophe
    case SingleQuote = "'";
    case Slash = '/';
    // Aka Asterisk
    case Star = '*';
    case Tilde = '~';
    case VerticalBar = '|';
}