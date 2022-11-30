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
 */
class GrammarLexerRe {
    public static function groupRe(...$choices): string {
        return '(' . implode('|', $choices) . ')';
    }

    public static function anyRe(...$choices): string {
        if (!count($choices)) {
            throw new \UnexpectedValueException("RE can't be empty");
        }
        return self::groupRe(...$choices) . '*';
    }

    public static function maybeRe(...$choices): string {
        if (!count($choices)) {
            throw new \UnexpectedValueException("RE can't be empty");
        }
        return self::groupRe(...$choices) . '?';
    }

    public static function pseudoTokenRe(): string {
        $commentRe = '#[^\r\n]*';
        //$ignoreRe = $whitespaceRe . self::anyRe('\\\r?\n' . $whitespaceRe) . $maybeRe($commentRe);
        $nameRe = '\w+';

        $hexNumberRe = '0[xX](?:_?[0-9a-fA-F])+';
        $binNumberRe = '0[bB](?:_?[01])+';
        $octNumberRe = '0[oO](?:_?[0-7])+';
        $decNumberRe = '(?:0(?:_?0)*|[1-9](?:_?[0-9])*)';
        $intNumberRe = self::groupRe($hexNumberRe, $binNumberRe, $octNumberRe, $decNumberRe);
        $exponentRe = '[eE][-+]?[0-9](?:_?[0-9])*';
        $pointFloatRe = self::groupRe('[0-9](?:_?[0-9])*\.(?:[0-9](?:_?[0-9])*)?', '\.[0-9](?:_?[0-9])*') . self::maybeRe($exponentRe);
        $expFloatRe = '[0-9](?:_?[0-9])*' . $exponentRe;
        $floatNumberRe = self::groupRe($pointFloatRe, $expFloatRe);
        $imageNumberRe = self::groupRe('[0-9](?:_?[0-9])*[jJ]', $floatNumberRe . '[jJ]');

        $numberRe = self::groupRe($imageNumberRe, $floatNumberRe, $intNumberRe);

        $allStringPrefixes = function (): array {
            // The valid string prefixes. Only contain the lower case versions, and don't contain any permutations (include 'fr', but not 'rf'). The various permutations will be generated.
            $validStringPrefixes = ['b', 'r', 'u', 'f', 'br', 'fr'];
            // if we add binary f-strings, add: ['fb', 'fbr']
            $result = [''];
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
            return $result;
        };
        // {'', 'Br', 'rF', 'rb', 'r', 'F', 'fR', 'U', 'R', 'br', 'FR', 'B', 'Fr', 'f', 'b', 'u', 'rf', 'Rb', 'BR', 'RF', 'bR', 'RB', 'rB', 'fr', 'Rf'}
        // Note that since _all_string_prefixes includes the empty string, $stringPrefixRe can be the empty string (making it optional).
        $stringPrefixRe = self::groupRe(...$allStringPrefixes());

        /*# Tail end of ' string.
        Single = r"[^'\\]*(?:\\.[^'\\]*)*'"
        # Tail end of " string.
        Double = r'[^"\\]*(?:\\.[^"\\]*)*"'
        # Tail end of ''' string.
        Single3 = r"[^'\\]*(?:(?:\\.|'(?!''))[^'\\]*)*'''"
        # Tail end of """ string.
        Double3 = r'[^"\\]*(?:(?:\\.|"(?!""))[^"\\]*)*"""'*/

        $tripleRe = self::groupRe($stringPrefixRe . '"""', $stringPrefixRe . '"""');

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
        $funnyRe = self::groupRe('\r?\n', $specialRe);

        // Not used:
        //PlainToken = group(Number, Funny, String, Name)
        //Token = Ignore + PlainToken

        // First (or only) line of ' or " string.
        $contStrRe = self::groupRe(
            $stringPrefixRe . "'[^\n'\\]*(?:\\.[^\n'\\]*)*"
            . self::groupRe("'", '\\\r?\n')
            . $stringPrefixRe . '"[^\n"\\]*(?:\\.[^\n"\\]*)*'
            . self::groupRe('"', "\\\r?\n")
        );
        $pseudoExtrasRe = self::groupRe("\\\r?\n|\Z", $commentRe, $tripleRe); // @todo: \Z
        return self::whitespaceRe() . self::groupRe($pseudoExtrasRe, $numberRe, $funnyRe, $contStrRe, $nameRe);
    }

    public static function whitespaceRe(): string {
        return '[ \f\t]*';
    }
}