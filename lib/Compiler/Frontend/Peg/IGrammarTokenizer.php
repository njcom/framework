<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\ITokenizer as IBaseTokenizer;

interface IGrammarTokenizer extends IBaseTokenizer {
    /**
     * getnext() in Python
     * @return mixed
     */
    public function nextToken(): Token;

    public function peekToken(): Token;

    public function index(): int;

    public function reset(int $index): void;

    public function lines(array $lineNumbers): array;

    public function diagnose(): Token;
    #public function report(bool $cached, bool $back): void;
    /*

        def get_last_non_whitespace_token(self) -> tokenize.TokenInfo:
     */
}