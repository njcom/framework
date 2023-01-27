<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use function iter\product;
use function Morpho\Base\permutations;

/**
 * Augment class for the GrammarLexer
 * https://github.com/python/cpython/blob/fc94d55ff453a3101e4c00a394d4e38ae2fece13/Lib/tokenize.py#L65
 */
class GrammarTokenizerRe {
    public const COMMENT_RE = '#[^\r\n]*';
    public const WHITESPACE_RE = '[ \f\t]*';
    public const NAME_RE = '\w+';
    // Numbers
    public const HEX_NUMBER_RE = '0[xX](?:_?[0-9a-fA-F])+';
    public const BIN_NUMBER_RE = '0[bB](?:_?[01])+';
    public const OCT_NUMBER_RE = '0[oO](?:_?[0-7])+';
    public const DEC_NUMBER_RE = '(?:0(?:_?0)*|[1-9](?:_?[0-9])*)';

    public static function endPatterns(): array {
        $endpats = [];
        # Tail end of ' string.
        $single = "[^'\\]*(?:\\.[^'\\]*)*'";
        # Tail end of " string.
        $double = '[^"\\]*(?:\\.[^"\\]*)*"';
        # Tail end of ''' string.
        $single3 = "[^'\\]*(?:(?:\\.|'(?!''))[^'\\]*)*'''";
        # Tail end of """ string.
        $double3 = '[^"\\]*(?:(?:\\.|"(?!""))[^"\\]*)*"""';
        foreach (self::allStringPrefixes() as $prefix) {
            $endpats[$prefix . "'"] = $single;
            $endpats[$prefix . '"'] = $double;
            $endpats[$prefix . "'''"] = $single3;
            $endpats[$prefix . '"""'] = $double3;
        }
        return $endpats;
    }

    public static function tripleQuotedPrefixes(): array {
        $tripleQuotedPrefixes = [];
        foreach (self::allStringPrefixes() as $prefix) {
            $tripleQuotedPrefixes[] = $prefix . '"""';
            $tripleQuotedPrefixes[] = $prefix . "'''";
        }
        return $tripleQuotedPrefixes;
    }

    public static function singleQuotedPrefixes(): array {
        $singleQuotedPrefixes = [];
        foreach (self::allStringPrefixes() as $prefix) {
            $singleQuotedPrefixes[] = $prefix . '"';
            $singleQuotedPrefixes[] = $prefix . "'";
        }
        return $singleQuotedPrefixes;
    }

    /**
     * @param ...$choices
     * @return string
     */
    public static function groupRe(...$choices): string {
        return '(' . implode('|', $choices) . ')';
    }

    /**
     * @param ...$choices
     * @return string
     */
    public static function anyRe(...$choices): string {
        if (!count($choices)) {
            throw new \UnexpectedValueException("RE can't be empty");
        }
        return self::groupRe(...$choices) . '*';
    }

    /**
     * @param ...$choices
     * @return string
     */
    public static function maybeRe(...$choices): string {
        if (!count($choices)) {
            throw new \UnexpectedValueException("RE can't be empty");
        }
        return self::groupRe(...$choices) . '?';
    }

    public static function intNumberRe(): string {
        return self::groupRe(self::HEX_NUMBER_RE, self::BIN_NUMBER_RE, self::OCT_NUMBER_RE, self::DEC_NUMBER_RE);
    }

    public static function floatNumberRe(): string {
        $exponentRe = '[eE][-+]?[0-9](?:_?[0-9])*';
        $pointFloatRe = self::groupRe('[0-9](?:_?[0-9])*\.(?:[0-9](?:_?[0-9])*)?', '\.[0-9](?:_?[0-9])*') . self::maybeRe($exponentRe);
        $expFloatRe = '[0-9](?:_?[0-9])*' . $exponentRe;
        return self::groupRe($pointFloatRe, $expFloatRe);
    }

    public static function imageNumberRe(): string {
        return self::groupRe('[0-9](?:_?[0-9])*[jJ]', self::floatNumberRe() . '[jJ]');
    }

    public static function pseudoTokenRe(): string {
        return self::WHITESPACE_RE
            . self::groupRe(
                self::pseudoExtrasRe(),
                self::numberRe(),
                self::funnyRe(),
                self::constStrRe(),
                self::NAME_RE
            );
    }

    public static function allStringPrefixes(): array {
        // The valid string prefixes. Only contain the lower case versions, and don't contain any permutations (include 'fr', but not 'rf'). The various permutations will be generated.
        $validStringPrefixes = ['b', 'r', 'u', 'f', 'br', 'fr'];
        // if we add binary f-strings, add: ['fb', 'fbr']
        $result = ['']; // it may be optional
        foreach ($validStringPrefixes as $prefix) {
            foreach (permutations(str_split($prefix)) as $row) {
                $pairs = [];
                foreach ($row as $c) {
                    $pairs[] = [$c, strtoupper($c)];
                }
                foreach (product(...$pairs) as $u) {
                    $result[] = implode('', $u);
                }
            }
        }
        // {'', 'Br', 'rF', 'rb', 'r', 'F', 'fR', 'U', 'R', 'br', 'FR', 'B', 'Fr', 'f', 'b', 'u', 'rf', 'Rb', 'BR', 'RF', 'bR', 'RB', 'rB', 'fr', 'Rf'}
        return $result;
    }

    public static function stringPrefixRe(): string {
        /*# Tail end of ' string.
        Single = r"[^'\\]*(?:\\.[^'\\]*)*'"
        # Tail end of " string.
        Double = r'[^"\\]*(?:\\.[^"\\]*)*"'
        # Tail end of ''' string.
        Single3 = r"[^'\\]*(?:(?:\\.|'(?!''))[^'\\]*)*'''"
        # Tail end of """ string.
        Double3 = r'[^"\\]*(?:(?:\\.|"(?!""))[^"\\]*)*"""'*/
        return self::groupRe(...self::allStringPrefixes());
    }

    public static function constStrRe(): string {
        // First (or only) line of ' or " string.
        $stringPrefixRe = self::stringPrefixRe();
        return self::groupRe(
            $stringPrefixRe . "'[^\\n'\\\\]*(\\.[^\\n'\\\\]*)*" . self::groupRe("'", '\\\\r?\\n'),
            $stringPrefixRe . '"[^\\n"\\\\]*(\\.[^\\n"\\\\]*)*' . self::groupRe('"', '\\\\r?\\n')
        );
    }

    private static function pseudoExtrasRe(): string {
        return self::groupRe("\\\r?\n|\Z", self::COMMENT_RE, self::tripleRe()); // @todo: \Z
    }

    private static function numberRe(): string {
        return self::groupRe(self::imageNumberRe(), self::floatNumberRe(), self::intNumberRe());
    }

    private static function tripleRe(): string {
        $stringPrefixRe = self::stringPrefixRe();
        return self::groupRe($stringPrefixRe . "'''", $stringPrefixRe . '"""');
    }

    private static function funnyRe(): string {
        /* Not used
                # Single-line ' or " string.
                String = group(StringPrefix + r"'[^\n'\\]*(?:\\.[^\n'\\]*)*'",
                               StringPrefix + r'"[^\n"\\]*(?:\\.[^\n"\\]*)*"')*/

        // Sorting in reverse order puts the long operators before their prefixes. Otherwise if = came before ==, == would get recognized as two instances of =.
        $exactTokenTypes = array_keys(TokenType::exactTypes());
        sort($exactTokenTypes);
        $exactTokenTypes = array_reverse($exactTokenTypes, true);
        // Port of `import re.escape`
        // https://github.com/python/cpython/blob/fc94d55ff453a3101e4c00a394d4e38ae2fece13/Lib/re/__init__.py#L253
        $escapeReSpecialChars = function (string $re): string {
            static $specialCharsMap = [
                '('    => "\\(",
                ')'    => "\\)",
                '['    => "\\[",
                ']'    => "\\]",
                '{'    => "\\{",
                '}'    => "\\}",
                '?'    => "\\?",
                '*'    => "\\*",
                '+'    => "\\+",
                '-'    => "\\-",
                '|'    => "\\|",
                '^'    => "\\^",
                '$'    => "\\$",
                '\\'   => "\\\\",
                '.'    => "\\.",
                '&'    => "\\&",
                '~'    => "\\~",
                '#'    => "\\#",
                ' '    => "\\ ",
                "\t"   => "\\\t",
                "\n"   => "\\\n",
                "\r"   => "\\\r",
                "\x0b" => "\\\x0b",
                "\x0c" => "\\\x0c",
            ];
            return strtr($re, $specialCharsMap);
        };
        $specialRe = self::groupRe(...\Morpho\Base\map($escapeReSpecialChars, $exactTokenTypes));
        return self::groupRe('\r?\n', $specialRe);
    }
}