<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Closure;
use Morpho\Compiler\Frontend\IParser;
use Morpho\Compiler\Frontend\SyntaxError;

/**
 * Base class for the PEG parsers
 * Based on https://github.com/python/cpython/blob/main/Tools/peg_generator/pegen/parser.py
 */
abstract class Parser implements IParser {
    private IGrammarTokenizer $tokenizer;
    private int $index;
    // private bool $verbose;
    private int $level;
    private array $cache;

    private const KEYWORDS = [];
    private  const SOFT_KEYWORDS = ['memo'];

    public function __construct(IGrammarTokenizer $tokenizer) {
        // tokenizer in Python
        $this->tokenizer = $tokenizer;
        //$this->verbose = $verbose;
        $this->level = 0;
        $this->cache = [];
        // Integer tracking whether we are in a left recursive rule or not. Can be useful for error reporting.
        $this->inRecursiveRule = 0;
        // Pass through common tokenizer methods.
        $this->index = $this->tokenizer->index();
    }

    /**
     * make_syntax_error() in Python.
     */
    public function mkSyntaxError(string $msg, string $filePath = null): SyntaxError {
        $tok = $this->tokenizer->diagnose();
        return new SyntaxError($msg, $filePath ?? '<unknown>', $tok->start, $tok->end, $tok->line);
    }

    abstract public function start(): mixed;

    protected function reset(int $index): void {
        $this->tokenizer->reset($index);
    }

    /*    public function showPeek(): string {
            $tok = $this->tokenizer->peek();
            return $tok->start[0] . '.' . $tok->start[1] . ': ' . static::TOKENS[$tok->type] . ':' . $tok->string . '!r';
        }*/

    protected function index(): int {
        return $this->tokenizer->index();
    }

    protected function name(): ?Token {
        return $this->nextTokenIf(__METHOD__, fn($tok) => $tok->type == TokenType::NAME && !in_array($tok->val, self::KEYWORDS));
    }

    protected function number(): ?Token {
        return $this->nextTokenIf(__METHOD__, fn($tok) => $tok->type == TokenType::NUMBER);
    }

    protected function string(): ?Token {
        return $this->nextTokenIf(__METHOD__, fn($tok) => $tok->type == TokenType::STRING);
    }

    protected function op(): ?Token {
        return $this->nextTokenIf(__METHOD__, fn ($tok) => $tok->type == TokenType::OP);
    }
    
    protected function typeComment(): ?Token {
        return $this->nextTokenIf(__METHOD__ , fn($tok) => $tok->type == TokenType::COMMENT);
    }

    protected function softKeyword(): ?Token {
        return $this->nextTokenIf(__METHOD__ , fn($tok) => $tok->type == TokenType::NAME && in_array($tok->val, self::SOFT_KEYWORDS));
    }

    protected function expect(string $type): ?Token {
        return $this->memoize(
            __METHOD__,
            function () use ($type) {
                $tok = $this->tokenizer->peekToken();
                if ($tok->val === $type) {
                    return $this->tokenizer->nextToken();
                }
                $exactTokenTypes = TokenType::exactTypes();
                if (isset($exactTokenTypes[$type])) {
                    if ($tok->type === $exactTokenTypes[$type]) {
                        return $this->tokenizer->nextToken();
                    }
                }
                /* todo:
                if type in token.__dict__:
                    if tok.type == token.__dict__[type]:
                        return self._tokenizer.getnext()*/
                if ($tok->type === TokenType::OP) { //  && $tok->type == $type is always false, so don't add it
                    return $this->tokenizer->nextToken();
                }
                return null;
            }
        );
    }

    protected function expectForced(mixed $res, string $expectation): ?Token {
        if (null === $res) {
            throw new $this->mkSyntaxError("expected $expectation");
        }
        return $res;
    }

    protected function positiveLookahead(callable $fn, ...$args): mixed {
        $index = $this->index();
        $ok = $fn(...$args);
        $this->reset($index);
        return $ok;
    }

    protected function negativeLookahead(callable $fn, ...$args): bool {
        $index = $this->index();
        $ok = $fn(...$args);
        $this->reset($index);
        return !$ok;
    }

    protected function memoize(string $fnId, Closure $fn): mixed {
        // todo
        return $fn();
    }

    private function nextTokenIf(string $memoizeKey, Closure $predicate): ?Token {
        return $this->memoize(
            $memoizeKey,
            function () use ($predicate): ?Token {
                $tok = $this->tokenizer->peekToken();
                return $predicate($tok) ? $this->tokenizer->nextToken() : null;
            }
        );
    }
}