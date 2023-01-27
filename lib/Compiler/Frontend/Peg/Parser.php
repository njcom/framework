<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Closure;
use Morpho\Compiler\Frontend\IParser;

/**
 * Base class for the PEG parsers
 * https://github.com/python/cpython/blob/main/Tools/peg_generator/pegen/parser.py
 */
abstract class Parser implements IParser {
    private GrammarTokenizer $lexer;




    public function __construct(GrammarTokenizer $lexer) {
        // tokenizer in Python
        $this->lexer = $lexer;
        //$this->level = 0;
        //$this->cache = [];
    }

    abstract public function start(): mixed;

    public function showPeek(): string {
        $tok = $this->lexer->peek();
        return $tok->start[0] . '.' . $tok->start[1] . ': ' . static::TOKENS[$tok->type] . ':' . $tok->string . '!r';
    }

    // vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv

    protected function mark(): int {
        return $this->lexer->mark();
    }

    protected function reset(int $index): void {
        $this->lexer->reset($index);
    }
    /*
    def __init__(self, tokenizer: Tokenizer, *, verbose: bool = False):
        self._tokenizer = tokenizer
        self._verbose = verbose
        self._level = 0
        self._cache: Dict[Tuple[Mark, str, Tuple[Any, ...]], Tuple[Any, Mark]] = {}
        # Pass through common tokenizer methods.
        # TODO: Rename to _mark and _reset.
        self.mark = self._tokenizer.mark
        self.reset = self._tokenizer.reset
    */
    /*
        def showpeek(self) -> str:
            tok = self._tokenizer.peek()
            return f"{tok.start[0]}.{tok.start[1]}: {token.tok_name[tok.type]}:{tok.string!r}"
    */
    protected function name(): ?TokenInfo {
        return $this->memoize(
            __METHOD__,
            function (): ?TokenInfo {
                $tok = $this->lexer->peek();
                if ($tok->type === Token::NAME) {
                    return $this->lexer->nextToken();
                }
                return null;
            }
        );
    }

    /*
        @memoize
        def number(self) -> Optional[tokenize.TokenInfo]:
            tok = self._tokenizer.peek()
            if tok.type == token.NUMBER:
                return self._tokenizer.getnext()
            return None
    
        @memoize
        def string(self) -> Optional[tokenize.TokenInfo]:
            tok = self._tokenizer.peek()
            if tok.type == token.STRING:
                return self._tokenizer.getnext()
            return None
    
        @memoize
        def op(self) -> Optional[tokenize.TokenInfo]:
            tok = self._tokenizer.peek()
            if tok.type == token.OP:
                return self._tokenizer.getnext()
            return None
    */
    protected function expect(string $type): ?TokenInfo {
        return $this->memoize(
            __METHOD__,
            function () use ($type) {
            }
        );
        /*
            @memoize
            def expect(self, type: str) -> Optional[tokenize.TokenInfo]:
                tok = self._tokenizer.peek()
                if tok.string == type:
                    return self._tokenizer.getnext()
                if type in exact_token_types:
                    if tok.type == exact_token_types[type]:
                        return self._tokenizer.getnext()
                if type in token.__dict__:
                    if tok.type == token.__dict__[type]:
                        return self._tokenizer.getnext()
                if tok.type == token.OP and tok.string == type:
                    return self._tokenizer.getnext()
                return None
        */
    }

    /*
       def positive_lookahead(self, func: Callable[..., T], *args: object) -> T:
           mark = self.mark()
           ok = func(*args)
           self.reset(mark)
           return ok
       def negative_lookahead(self, func: Callable[..., object], *args: object) -> bool:
           mark = self.mark()
           ok = func(*args)
           self.reset(mark)
           return not ok
    
       def make_syntax_error(self, filename: str = "<unknown>") -> SyntaxError:
           tok = self._tokenizer.diagnose()
           return SyntaxError(
               "pegen parse failure", (filename, tok.start[0], 1 + tok.start[1], tok.line)
           )
    */
    protected function memoize(string $fnId, Closure $fn): mixed {
        // todo
        return $fn();
    }
    /*
        def memoize(method: F) -> F:
            """Memoize a symbol method."""
            method_name = method.__name__
    
            def memoize_wrapper(self: P, *args: object) -> T:
       mark = self.mark()
       key = mark, method_name, args
       # Fast path: cache hit, and not verbose.
       if key in self._cache and not self._verbose:
           tree, endmark = self._cache[key]
           self.reset(endmark)
           return tree
       # Slow path: no cache hit, or verbose.
       verbose = self._verbose
       argsr = ",".join(repr(arg) for arg in args)
       fill = "  " * self._level
       if key not in self._cache:
           if verbose:
               print(f"{fill}{method_name}({argsr}) ... (looking at {self.showpeek()})")
           self._level += 1
           tree = method(self, *args)
           self._level -= 1
           if verbose:
               print(f"{fill}... {method_name}({argsr}) -> {tree!s:.200}")
           endmark = self.mark()
           self._cache[key] = tree, endmark
       else:
           tree, endmark = self._cache[key]
           if verbose:
               print(f"{fill}{method_name}({argsr}) -> {tree!s:.200}")
           self.reset(endmark)
       return tree
    
            memoize_wrapper.__wrapped__ = method  # type: ignore
            return cast(F, memoize_wrapper)
    
    
        def memoize_left_rec(method: Callable[[P], Optional[T]]) -> Callable[[P], Optional[T]]:
            """Memoize a left-recursive symbol method."""
            method_name = method.__name__
    
            def memoize_left_rec_wrapper(self: P) -> Optional[T]:
       mark = self.mark()
       key = mark, method_name, ()
       # Fast path: cache hit, and not verbose.
       if key in self._cache and not self._verbose:
           tree, endmark = self._cache[key]
           self.reset(endmark)
           return tree
       # Slow path: no cache hit, or verbose.
       verbose = self._verbose
       fill = "  " * self._level
       if key not in self._cache:
           if verbose:
               print(f"{fill}{method_name} ... (looking at {self.showpeek()})")
           self._level += 1
    
           # For left-recursive rules we manipulate the cache and
           # loop until the rule shows no progress, then pick the
           # previous result.  For an explanation why this works, see
           # https://github.com/PhilippeSigaud/Pegged/wiki/Left-Recursion
           # (But we use the memoization cache instead of a static
           # variable; perhaps this is similar to a paper by Warth et al.
           # (http://web.cs.ucla.edu/~todd/research/pub.php?id=pepm08).
    
           # Prime the cache with a failure.
           self._cache[key] = None, mark
           lastresult, lastmark = None, mark
           depth = 0
           if verbose:
               print(f"{fill}Recursive {method_name} at {mark} depth {depth}")
    
           while True:
               self.reset(mark)
               result = method(self)
               endmark = self.mark()
               depth += 1
               if verbose:
                   print(
                       f"{fill}Recursive {method_name} at {mark} depth {depth}: {result!s:.200} to {endmark}"
                   )
               if not result:
                   if verbose:
                       print(f"{fill}Fail with {lastresult!s:.200} to {lastmark}")
                   break
               if endmark <= lastmark:
                   if verbose:
                       print(f"{fill}Bailing with {lastresult!s:.200} to {lastmark}")
                   break
               self._cache[key] = lastresult, lastmark = result, endmark
    
           self.reset(lastmark)
           tree = lastresult
    
           self._level -= 1
           if verbose:
               print(f"{fill}{method_name}() -> {tree!s:.200} [cached]")
           if tree:
               endmark = self.mark()
           else:
               endmark = mark
               self.reset(endmark)
           self._cache[key] = tree, endmark
       else:
           tree, endmark = self._cache[key]
           if verbose:
               print(f"{fill}{method_name}() -> {tree!s:.200} [fresh]")
           if tree:
               self.reset(endmark)
       return tree
    
            memoize_left_rec_wrapper.__wrapped__ = method  # type: ignore
            return memoize_left_rec_wrapper
    */
}