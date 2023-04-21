<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\Location;

use Stringable;

use function Morpho\Base\q;
use function Morpho\Base\qq;

/**
 * https://github.com/python/cpython/blob/fc94d55ff453a3101e4c00a394d4e38ae2fece13/Lib/tokenize.py#L46
 */
readonly class Token implements Stringable {
    public TokenType $type;
    public string $val;
    public Location $start;
    public Location $end;
    public string $line;

    public function __construct(TokenType $type, string $val, Location $start, Location $end, string $line) {
        $this->type = $type;
        $this->val = $val;
        $this->start = $start;
        $this->end = $end;
        $this->line = $line;
    }

    public function __toString(): string {
        $q = function (string $s): string {
            $s = strtr($s, ["\n" => "\\n", "\\" => "\\\\"]);
            if ($s === '') {
                return "''";
            }
            $singleQuotePos = strpos($s, "'");
            $doubleQuotePos = strpos($s, '"');
            if (false !== $doubleQuotePos && false !== $singleQuotePos) {
                /*
                if ($singleQuotePos < $doubleQuotePos) {
                    return qq(strtr($s, '"', '\\"'));
                }
                */
                return q(str_replace("'", "\\'", $s));
            }
            /*
            if (false !== $singleQuotePos) {
                return qq(strtr($s, '"', '\\"'));
            }
            */
            if (false !== $singleQuotePos) {
                return qq(str_replace('"', '\\"', $s));
            }
            return q($s);
        };
        // @todo: rename `string` to `val`
        return 'TokenInfo(type=' . $this->type->value . ' (' . ($this->type->name) . '), string=' . $q($this->val) . ', start=(' . $this->start->lineNo . ', ' . $this->start->columnNo . '), end=(' . $this->end->lineNo . ', ' . $this->end->columnNo . '), line=' . $q($this->line) . ")";
    }
}