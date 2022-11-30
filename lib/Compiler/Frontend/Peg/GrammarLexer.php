<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
/**
 * The implementation is based on Python's PEG:
 * 1. https://medium.com/@gvanrossum_83706/peg-parsing-series-de5d41b2ed60
 * 2. https://www.python.org/dev/peps/pep-0617/
 * 3. https://www.youtube.com/watch?v=QppWTvh7_sI
 */
namespace Morpho\Compiler\Frontend\Peg;

use Generator;
use Morpho\Base\NotImplementedException;
use Morpho\Compiler\Frontend\ILexer;
use Morpho\Compiler\Frontend\Location;
use RuntimeException;

use function iter\product;
use function Morpho\Base\caseVal;
use function Morpho\Base\last;
use function Morpho\Base\permutations;

/**
 * https://github.com/python/cpython/blob/main/Tools/peg_generator/pegen/tokenizer.py
 */
class GrammarLexer implements ILexer {
    private int $index = 0;

    /**
     * @var array TokenInfo[]
     */
    private array $tokens = [];

    private Generator $tokenGen;

    //_tokens: List[tokenize.TokenInfo]

    public function __construct(Generator $tokenGen) {
        $this->tokenGen = $tokenGen;
    }

    /**
     * https://github.com/python/cpython/blob/fc94d55ff453a3101e4c00a394d4e38ae2fece13/Lib/tokenize.py#L433
     */
    public static function tokens(string $filePath): Generator {
        $lnum = $parenlev = $continued = 0;
        $numchars = '0123456789';
        $contstr = '';
        $needcont = 0;
        $contline = null;
        $indents = [0];
        $lastLine = '';
        $line = '';
        $strstart = [0, 0];
        $tabsize = 4;
        try {
            $stream = fopen($filePath, 'r');
            while (true) { // loop over lines in stream
                $line = fgets($stream);
                if (false === $line) {
                    break;
                }
                $lnum++;
                $pos = 0;
                $max = mb_strlen($line);

                if ($contstr) { // continued string
                    if ($line === '') {
                        throw new TokenException("EOF in multi-line string", $strstart);
                    }
                    throw new NotImplementedException();
                    /*

                                endmatch = endprog.match(line)
                                if endmatch:
                                    pos = end = endmatch.end(0)
                                    yield TokenInfo(STRING, contstr + line[:end],
                                           strstart, (lnum, end), contline + line)
                                    contstr, needcont = '', 0
                                    contline = None
                                elif needcont and line[-2:] != '\\\n' and line[-3:] != '\\\r\n':
                                    yield TokenInfo(ERRORTOKEN, contstr + line,
                                               strstart, (lnum, len(line)), contline)
                                    contstr = ''
                                    contline = None
                                    continue
                                else:
                                    contstr = contstr + line
                                    contline = contline + line
                                    continue

                     */
                } elseif ($parenlev === 0 && !$continued) { // new statement
                    if ($line === '') {
                        break;
                    }
                    $column = 0;
                    while ($pos < $max) { // measure leading whitespace
                        if ($line[$pos] === ' ') {
                            $column++;
                        } elseif ($line[$pos] === "\t") {
                            $column = (floor($column / $tabsize) + 1) * $tabsize;
                        } elseif ($line[$pos] === "\f") {
                            $column = 0;
                        } else {
                            break;
                        }
                        $pos++;
                    }
                    if ($pos === $max) {
                        break;
                    }
                    if (in_array($line[$pos], ['#', "\r", "\n"])) { // skip comments or blank lines
                        if ($line[$pos] === '#') {
                            $commentToken = rtrim(mb_substr($line, $pos), "\r\n");
                            yield new TokenInfo(TokenType::COMMENT, $commentToken, new Location($lnum, $pos), new Location($lnum, $pos + mb_strlen($commentToken)), $line);
                            $pos += mb_strlen($commentToken);
                        }
                        yield new TokenInfo(TokenType::NL, mb_substr($line, $pos), new Location($lnum, $pos), new Location($lnum, mb_strlen($line)), $line);
                        continue;
                    }
                    if ($column > last($indents)) { // count indents or dedents
                        $indents[] = $column;
                        yield new TokenInfo(TokenType::INDENT, mb_substr($line, 0, $pos), new Location($lnum, 0), new Location($lnum, $pos), $line);
                    }
                    while ($column < last($indents)) {
                        if (!in_array($column, $indents)) {
                            throw new IndentationException("Unindent does not match any outer indentation level", $lnum, $pos, $line);
                        }
                        $indents = array_slice($indents, 0, -1);
                        yield new TokenInfo(TokenType::INDENT, '', new Location($lnum, $pos), new Location($lnum, $pos), $line);
                    }
                } else { // continued statement
                    if ('' !== $line) {
                        throw new TokenException("EOF in multi-line statement", [$lnum, 0]);
                    }
                    $continued = 0;
                }


                $pseudoTokenRe = GrammarLexerRe::pseudoTokenRe();
                d($pseudoTokenRe);

                while ($pos < $max) {
                    if (preg_match($pseudoTokenRe, $line, $matches, -1, $pos)) {  # scan for tokens
                        d($matches);
                        /*
                                start, end = pseudomatch.span(1)
                                spos, epos, pos = (lnum, start), (lnum, end), end
                                if start == end:
                                    continue
                                token, initial = line[start:end], line[start]

                                if (initial in numchars or                 # ordinary number
                                    (initial == '.' and token != '.' and token != '...')):
                                    yield TokenInfo(NUMBER, token, spos, epos, line)
                                elif initial in '\r\n':
                                    if parenlev > 0:
                                        yield TokenInfo(NL, token, spos, epos, line)
                                    else:
                                        yield TokenInfo(NEWLINE, token, spos, epos, line)

                                elif initial == '#':
                                    assert not token.endswith("\n")
                                    yield TokenInfo(COMMENT, token, spos, epos, line)

                                elif token in triple_quoted:
                                    endprog = _compile(endpats[token])
                                    endmatch = endprog.match(line, pos)
                                    if endmatch:                           # all on one line
                                        pos = endmatch.end(0)
                                        token = line[start:pos]
                                        yield TokenInfo(STRING, token, spos, (lnum, pos), line)
                                    else:
                                        strstart = (lnum, start)           # multiple lines
                                        contstr = line[start:]
                                        contline = line
                                        break

                                # Check up to the first 3 chars of the token to see if
                                #  they're in the single_quoted set. If so, they start
                                #  a string.
                                # We're using the first 3, because we're looking for
                                #  "rb'" (for example) at the start of the token. If
                                #  we switch to longer prefixes, this needs to be
                                #  adjusted.
                                # Note that initial == token[:1].
                                # Also note that single quote checking must come after
                                #  triple quote checking (above).
                                elif (initial in single_quoted or
                                      token[:2] in single_quoted or
                                      token[:3] in single_quoted):
                                    if token[-1] == '\n':                  # continued string
                                        strstart = (lnum, start)
                                        # Again, using the first 3 chars of the
                                        #  token. This is looking for the matching end
                                        #  regex for the correct type of quote
                                        #  character. So it's really looking for
                                        #  endpats["'"] or endpats['"'], by trying to
                                        #  skip string prefix characters, if any.
                                        endprog = _compile(endpats.get(initial) or
                                                           endpats.get(token[1]) or
                                                           endpats.get(token[2]))
                                        contstr, needcont = line[start:], 1
                                        contline = line
                                        break
                                    else:                                  # ordinary string
                                        yield TokenInfo(STRING, token, spos, epos, line)

                                elif initial.isidentifier():               # ordinary name
                                    yield TokenInfo(NAME, token, spos, epos, line)
                                elif initial == '\\':                      # continued stmt
                                    continued = 1
                                else:
                                    if initial in '([{':
                                        parenlev += 1
                                    elif initial in ')]}':
                                        parenlev -= 1
                                    yield TokenInfo(OP, token, spos, epos, line)
                        */
                    } else {
                        yield new TokenInfo(TokenType::ERRORTOKEN, $line[$pos], new Location($lnum, $pos), new Location($lnum, $pos + 1), $line);
                        $pos++;
                    }
                    // Add an implicit NEWLINE if the input doesn't end in one
                    if (mb_strlen($lastLine) && !in_array(last($lastLine), ["\r", "\n"]) && !str_starts_with(trim($lastLine), '#')) {
                        yield new TokenInfo(TokenTYpe::NEWLINE, '', new Location($lnum - 1, mb_strlen($lastLine)), new Location($lnum - 1, mb_strlen($lastLine) + 1), '');
                    }
                    foreach (array_slice($indents, 1) as $indent) { // pop remaining indent levels
                        yield new TokenInfo(TokenType::DEDENT, '', new Location($lnum, 0), new Location($lnum, 0), '');
                    }
                    yield new TokenInfo(TokenType::ENDMARKER, '', new Location($lnum, 0), new Location($lnum, 0), '');
                }
                dd();
            }
            if (!feof($stream)) {
                throw new RuntimeException('Unexpected end of the stream');
            }
        } finally {
            if (isset($stream)) {
                fclose($stream);
            }
        }
    }

    /*

    def shorttok(tok: tokenize.TokenInfo) -> str:
        return "%-25.25s" % f"{tok.start[0]}.{tok.start[1]}: {token.tok_name[tok.type]}:{tok.string!r}"
    */

    /**
     * getnext() in Python
     */
    public function nextToken(): TokenInfo {
        /*"""Return the next token and updates the index."""
        cached = True
        while self._index == len(self._tokens):
            tok = next(self._tokengen)
            if tok.type in (tokenize.NL, tokenize.COMMENT):
                continue
            if tok.type == token.ERRORTOKEN and tok.string.isspace():
                continue
            self._tokens.append(tok)
            cached = False
        tok = self._tokens[self._index]
        self._index += 1
        if self._verbose:
            self.report(cached, False)
        return tok

    def peek(self) -> tokenize.TokenInfo:*/
    }


    /**
     * Return the next token *without* updating the index.
     * @return TokenInfo
     */
    public function peek(): TokenInfo {
        while ($this->index === count($this->tokens)) {
            $this->tokenGen->next();
            $tok = $this->tokenGen->current();
            if (in_array($tok->type, [Tokenize . NL, Tokenize . COMMENT])) {
                continue;
            }
            if ($tok->type === Token . ERRORTOKEN && $tok->string->isSpace()) {
                continue;
            }
            $this->tokens[] = $tok;
        }
        return $this->tokens[$this->index];
    }
    /*        def diagnose(self) -> tokenize.TokenInfo:
                if not self._tokens:
                    self.getnext()
                return self._tokens[-1]*/

    # get_last_non_whitespace_token()
    public function lastNonWhitespaceToken(): TokenInfo {
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
     * def get_lines(self, line_numbers: List[int]) -> List[str]:
     * @return void
     */
    public function lines() {
        throw new NotImplementedException();
    }

    // mark() in Python
    public function mark(): int {
        return $this->index;
    }

    public function reset(int $index): void {
        $this->index = $index;
    }
    /*
        public function __invoke(mixed $context): mixed {
            throw new NotImplementedException();
        }*/
}