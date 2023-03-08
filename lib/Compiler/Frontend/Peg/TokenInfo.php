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

/**
 * https://github.com/python/cpython/blob/fc94d55ff453a3101e4c00a394d4e38ae2fece13/Lib/tokenize.py#L46
 */
readonly class TokenInfo implements Stringable {
    public TokenType $type;
    // @todo: rename to $val
    public string $string;
    public Location $start;
    public Location $end;
    public string $line;

    public function __construct(TokenType $type, string $string, Location $start, Location $end, string $line) {
        $this->type = $type;
        $this->string = $string;
        $this->start = $start;
        $this->end = $end;
        $this->line = $line;
    }

    public function __toString(): string {
        $escape = function (string $line) {
            return strtr(
                $line,
                [
                    "\n" => "\\n",
                    "\\" => "\\\\",
                ]
            );
        };
        return 'TokenInfo(type=' . $this->type->value . ' (' . ($this->type->name) . '), string=' . q($escape($this->string)) . ', start=(' . $this->start->lineNo . ', ' . $this->start->columnNo . '), end=(' . $this->end->lineNo . ', ' . $this->end->columnNo . '), line=' . q($escape($this->line)) . ")";
    }
}