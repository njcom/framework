<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

/**
 * https://github.com/python/cpython/blob/main/Lib/token.py
 */
enum TokenType: int {
    /*
    Got with:
        import token
        token.tok_name
    */
    case ENDMARKER = 0;
    case NAME = 1;
    case NUMBER = 2;
    case STRING = 3;
    case NEWLINE = 4;
    case INDENT = 5;
    case DEDENT = 6;
    case LPAR = 7;
    case RPAR = 8;
    case LSQB = 9;
    case RSQB = 10;
    case COLON = 11;
    case COMMA = 12;
    case SEMI = 13;
    case PLUS = 14;
    case MINUS = 15;
    case STAR = 16;
    case SLASH = 17;
    case VBAR = 18;
    case AMPER = 19;
    case LESS = 20;
    case GREATER = 21;
    case EQUAL = 22;
    case DOT = 23;
    case PERCENT = 24;
    case LBRACE = 25;
    case RBRACE = 26;
    case EQEQUAL = 27;
    case NOTEQUAL = 28;
    case LESSEQUAL = 29;
    case GREATEREQUAL = 30;
    case TILDE = 31;
    case CIRCUMFLEX = 32;
    case LEFTSHIFT = 33;
    case RIGHTSHIFT = 34;
    case DOUBLESTAR = 35;
    case PLUSEQUAL = 36;
    case MINEQUAL = 37;
    case STAREQUAL = 38;
    case SLASHEQUAL = 39;
    case PERCENTEQUAL = 40;
    case AMPEREQUAL = 41;
    case VBAREQUAL = 42;
    case CIRCUMFLEXEQUAL = 43;
    case LEFTSHIFTEQUAL = 44;
    case RIGHTSHIFTEQUAL = 45;
    case DOUBLESTAREQUAL = 46;
    case DOUBLESLASH = 47;
    case DOUBLESLASHEQUAL = 48;
    case AT = 49;
    case ATEQUAL = 50;
    case RARROW = 51;
    case ELLIPSIS = 52;
    case COLONEQUAL = 53;
    case OP = 54;
    case AWAIT = 55;
    case ASYNC = 56;
    case TYPE_IGNORE = 57;
    case TYPE_COMMENT = 58;
    case SOFT_KEYWORD = 59;
    case ERRORTOKEN = 60;
    case COMMENT = 61;
    case NL = 62;
    case ENCODING = 63;
    case N_TOKENS = 64;
    case NT_OFFSET = 256;

    /**
     * EXACT_TOKEN_TYPES in Python
     * @return \Morpho\Compiler\Frontend\Peg\TokenType[]
     */
    public static function exactTypes(): array {
        return [
            '!=' => self::NOTEQUAL,
            '%' => self::PERCENT,
            '%=' => self::PERCENTEQUAL,
            '&' => self::AMPER,
            '&=' => self::AMPEREQUAL,
            '(' => self::LPAR,
            ')' => self::RPAR,
            '*' => self::STAR,
            '**' => self::DOUBLESTAR,
            '**=' => self::DOUBLESTAREQUAL,
            '*=' => self::STAREQUAL,
            '+' => self::PLUS,
            '+=' => self::PLUSEQUAL,
            ',' => self::COMMA,
            '-' => self::MINUS,
            '-=' => self::MINEQUAL,
            '->' => self::RARROW,
            '.' => self::DOT,
            '...' => self::ELLIPSIS,
            '/' => self::SLASH,
            '//' => self::DOUBLESLASH,
            '//=' => self::DOUBLESLASHEQUAL,
            '/=' => self::SLASHEQUAL,
            ':' => self::COLON,
            ':=' => self::COLONEQUAL,
            ';' => self::SEMI,
            '<' => self::LESS,
            '<<' => self::LEFTSHIFT,
            '<<=' => self::LEFTSHIFTEQUAL,
            '<=' => self::LESSEQUAL,
            '=' => self::EQUAL,
            '==' => self::EQEQUAL,
            '>' => self::GREATER,
            '>=' => self::GREATEREQUAL,
            '>>' => self::RIGHTSHIFT,
            '>>=' => self::RIGHTSHIFTEQUAL,
            '@' => self::AT,
            '@=' => self::ATEQUAL,
            '[' => self::LSQB,
            ']' => self::RSQB,
            '^' => self::CIRCUMFLEX,
            '^=' => self::CIRCUMFLEXEQUAL,
            '{' => self::LBRACE,
            '|' => self::VBAR,
            '|=' => self::VBAREQUAL,
            '}' => self::RBRACE,
            '~' => self::TILDE,
        ];
    }
}