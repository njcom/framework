<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Morpho\Compiler\Frontend\ITokenizer as IBaseTokenizer;

interface IGrammarTokenizer extends IBaseTokenizer {
    /**
     * Returns the next token and updates the index.
     * getnext() in Python
     */
    public function nextToken(): Token;

    /**
     * Returns the next token *without* updating the index.
     * peek() in Python
     * @return \Morpho\Compiler\Frontend\Peg\Token
     */
    public function peekToken(): Token;

    public function index(): int;

    public function reset(int $index): void;

    public function lines(array $lineNumbers): array;

    public function diagnose(): Token;

    /**
     * get_last_non_whitespace_token() in Python
     */
    public function lastNonWhitespaceToken(): Token;

    #public function report(bool $cached, bool $back): void;
}