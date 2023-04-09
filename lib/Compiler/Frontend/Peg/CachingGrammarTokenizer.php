<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Generator;
use Morpho\Base\NotImplementedException;
use Morpho\Compiler\Frontend\ITokenizer;

/**
 * Based on https://github.com/python/cpython/blob/main/Tools/peg_generator/pegen/tokenizer.py
 */
class CachingGrammarTokenizer implements ITokenizer {
    private array $tokens = [];
    private int $index = 0;
    private Generator $tokenGen;
    private string $path;
    private bool $verbose;
    private array $lines = []; #Dict[int, str] = {}

    public function __construct(Generator $tokenGen, string $path = '', bool $verbose = false) {
        $this->tokenGen = $tokenGen;
        $this->path = $path;
        $this->verbose = $verbose;
    }

    /**
     * Return the next token *without* updating the index.
     * @return \Morpho\Compiler\Frontend\Peg\TokenInfo
     */
    public function peek(): TokenInfo {
        while ($this->index == count($this->tokens)) {
            /** @var TokenInfo $tok */
            $tok = $this->tokenGen->current();
            $this->tokenGen->next();
            if ($tok->type == TokenType::NL || $tok->type == TokenType::COMMENT) {
                continue;
            }
            if ($tok->type == TokenType::ERRORTOKEN && ctype_space($tok->val)) {
                continue;
            }
            if ($tok->type == TokenType::NEWLINE && $this->tokens && $this->tokens[count($this->tokens) - 1]->type == TokenType::NL) {
                continue;
            }
            $this->tokens[] = $tok;
            if (!$this->path) {
                $this->lines[$tok->start->lineNo] = $tok->line;
            }
        }
        return $this->tokens[$this->index];
    }

    public function index(): int {
        return $this->index;
    }

    public function __invoke(mixed $val): mixed {
        throw new NotImplementedException();
    }
}