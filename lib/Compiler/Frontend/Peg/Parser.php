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
    private int $level;
    private array $cache;
    private const KEYWORDS = [];
    private const SOFT_KEYWORDS = ['memo'];

    public function __construct(IGrammarTokenizer $tokenizer) {
        $this->tokenizer = $tokenizer;
        $this->level = 0;
        $this->cache = [];
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

    protected function index(): int {
        return $this->tokenizer->index();
    }

    protected function name(): ?Token {
        return $this->memoize(
            __METHOD__,
            function () {
                $tok = $this->tokenizer->peekToken();
                if ($tok->type == TokenType::NAME && !in_array($tok->val, self::KEYWORDS)) {
                    return $this->tokenizer->nextToken();
                }
                return null;
            },
        );
    }

    protected function number(): ?Token {
        return $this->memoize(
            __METHOD__,
            function () {
                $tok = $this->tokenizer->peekToken();
                if ($tok->type == TokenType::NUMBER) {
                    return $this->tokenizer->nextToken();
                }
                return null;
            },
        );
    }

    protected function string(): ?Token {
        return $this->memoize(
            __METHOD__,
            function () {
                $tok = $this->tokenizer->peekToken();
                if ($tok->type == TokenType::STRING) {
                    return $this->tokenizer->nextToken();
                }
                return null;
            },
        );
    }

    protected function op(): ?Token {
        return $this->memoize(
            __METHOD__,
            function () {
                $tok = $this->tokenizer->peekToken();
                if ($tok->type == TokenType::OP) {
                    return $this->tokenizer->nextToken();
                }
                return null;
            },
        );
    }
    
    protected function typeComment(): ?Token {
        return $this->memoize(
            __METHOD__,
            function () {
                $tok = $this->tokenizer->peekToken();
                if ($tok->type == TokenType::COMMENT) {
                    return $this->tokenizer->nextToken();
                }
                return null;
            },
        );
    }

    protected function softKeyword(): ?Token {
        return $this->memoize(
            __METHOD__,
            function () {
                $tok = $this->tokenizer->peekToken();
                if ($tok->type == TokenType::NAME && in_array($tok->val, self::SOFT_KEYWORDS)) {
                    return $this->tokenizer->nextToken();
                }
                return null;
            },
        );
    }

    protected function expect(string $type): ?Token {
        return $this->memoize(
            __METHOD__,
            function ($type) {
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
                if ($tok->type === TokenType::OP && $tok->val == $type) {
                    return $this->tokenizer->nextToken();
                }
                return null;
            },
            $type,
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

    protected function memoize(string $fnId, Closure $fn, ...$args): mixed {
        $index = $this->index();
        $key = md5(serialize([$index, $fnId, $args]));
        if (isset($this->cache[$key])) {
            [$tree, $endIndex] = $this->cache[$key];
            $this->reset($endIndex);
            return $tree;
        }
        $this->level++;
        $tree = $fn(...$args);
        $this->level--;
        $endIndex = $this->index();
        $this->cache[$key] = [$tree, $endIndex];
        return $tree;
    }
}