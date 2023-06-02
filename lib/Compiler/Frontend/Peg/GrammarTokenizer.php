<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Iterator;
use Morpho\Base\NotImplementedException;

/**
 * Based on https://github.com/python/cpython/blob/main/Tools/peg_generator/pegen/tokenizer.py
 */
class GrammarTokenizer implements IGrammarTokenizer {
    private int $index = 0;

    /**
     * @var array Token[]
     */
    private array $tokens = [];

    private Iterator $tokenGen;

    public function __construct(Iterator $tokenGen) {
        $this->tokenGen = $tokenGen;
    }

    /*
    def shorttok(tok: tokenize.TokenInfo) -> str:
        return "%-25.25s" % f"{tok.start[0]}.{tok.start[1]}: {token.tok_name[tok.type]}:{tok.string!r}"
    */

    public function nextToken(): Token {
        $tok = $this->peekToken();
        $this->index++;
        return $tok;
    }

    public function peekToken(): Token {
        while ($this->index === count($this->tokens)) {
            $tok = $this->tokenGen->current();
            $this->tokenGen->next();
            if (in_array($tok->type, [TokenType::NL, TokenType::COMMENT])) {
                continue;
            }
            if ($tok->type === TokenType::ERRORTOKEN && ctype_space($tok->val)) {
                continue;
            }
            $this->tokens[] = $tok;
        }
        return $this->tokens[$this->index];
    }

    public function diagnose(): Token {
        if (!$this->tokens) {
            $this->nextToken();
        }
        return $this->tokens[count($this->tokens) - 1];
    }

    public function lastNonWhitespaceToken(): Token {
        throw new NotImplementedException();
        /*
        for tok in reversed(self._tokens[: self._index]):
            if tok.type != tokenize.ENDMARKER and (
                tok.type < tokenize.NEWLINE or tok.type > tokenize.DEDENT
            ):
                break
        return tok
        */
    }

    /**
     * get_lines(self, line_numbers: List[int]) -> List[str]:
     */
    public function lines(array $lineNumbers): array {
        throw new NotImplementedException();
    }

    /**
     * mark() in Python
     */
    public function index(): int {
        return $this->index;
    }

    public function reset(int $index): void {
        $this->index = $index;
    }
}