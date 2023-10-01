<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

use Iterator;
use Morpho\Base\Must;
use Morpho\Base\NotImplementedException;

use RuntimeException;

use UnexpectedValueException;

use function Morpho\Base\last;
use function Morpho\Base\mkStream;

/**
 * Based on https://github.com/python/cpython/blob/fc94d55ff453a3101e4c00a394d4e38ae2fece13/Lib/tokenize.py
 */
class GeneralTokenizer {
    /**
     * @param resource|string $stream
     * @return Iterator
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function tokenize($stream): Iterator {
        if (is_string($stream)) {
            $stream = mkStream($stream);
        }
        $lineNum = $parenLevel = $continued = 0;
        $numChars = '0123456789';
        $contStr = '';
        $needCont = 0;
        $contLine = null;
        $indents = [0];
        $line = '';
        $endProgRe = null;
        $strStart = [0, 0];
        $tabSize = 4;
        if (stream_get_meta_data($stream)['seekable']) {
            rewind($stream);
        }

        $pseudoTokenRe = GeneralTokenizerRe::pseudoTokenRe();
        $tripleQuoted = GeneralTokenizerRe::tripleQuotedPrefixes();
        $singleQuoted = GeneralTokenizerRe::singleQuotedPrefixes();
        $endPatterns = GeneralTokenizerRe::endPatterns();

        while (true) { // loop over lines in stream
            $lastLine = $line;
            $line = fgets($stream);
            $lineNum++;
            $pos = $max = 0;
            if (false !== $line) {
                $max = mb_strlen($line);
            }

            if ($contStr) { // continued string
                if (false === $line) {
                    throw new TokenException("EOF in multi-line string", $strStart);
                }
                if (preg_match('~' . $endProgRe . '~AsDu', $line, $match, 0, $pos)) {
                    $pos = $end = mb_strlen($match[0]);
                    /** @var int $end */
                    yield new Token(TokenType::STRING, $contStr . mb_substr($line, 0, $end), $strStart, [$lineNum, $end], $contLine . $line);
                    $contStr = '';
                    $needCont = 0;
                    $contLine = null;
                } elseif ($needCont && !str_ends_with($line, "\\\n") && !str_ends_with($line, "\\\r\n")) {
                    yield new Token(TokenType::ERRORTOKEN, $contStr . $line, $strStart, [$lineNum, mb_strlen($line)], $contLine);
                    $contStr = '';
                    $contLine = null;
                    continue;
                } else {
                    $contStr .= $line;
                    $contLine = $contLine . $line;
                    continue;
                }
            } elseif ($parenLevel === 0 && !$continued) { // new statement
                if ($line === '') {
                    break;
                }
                $column = 0;
                while ($pos < $max) { // measure leading whitespace
                    if ($line[$pos] === ' ') {
                        $column++;
                    } elseif ($line[$pos] === "\t") {
                        $column = (floor($column / $tabSize) + 1) * $tabSize;
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
                if (str_contains("#\r\n", $line[$pos])) { // skip comments or blank lines
                    if ($line[$pos] === '#') {
                        $commentToken = rtrim(mb_substr($line, $pos), "\r\n");
                        yield new Token(TokenType::COMMENT, $commentToken, [$lineNum, $pos], [$lineNum, $pos + mb_strlen($commentToken)], $line);
                        $pos += mb_strlen($commentToken);
                    }
                    yield new Token(TokenType::NL, mb_substr($line, $pos), [$lineNum, $pos], [$lineNum, mb_strlen($line)], $line);
                    continue;
                }
                if ($column > last($indents)) { // count indents or dedents
                    $indents[] = $column;
                    yield new Token(TokenType::INDENT, mb_substr($line, 0, $pos), [$lineNum, 0], [$lineNum, $pos], $line);
                }
                while ($column < last($indents)) {
                    if (!in_array($column, $indents)) {
                        throw new IndentationException("Unindent does not match any outer indentation level", $lineNum, $pos, $line);
                    }
                    $indents = array_slice($indents, 0, -1);
                    yield new Token(TokenType::DEDENT, '', [$lineNum, $pos], [$lineNum, $pos], $line);
                }
            } else { // continued statement
                if ('' === $line) {
                    throw new TokenException("EOF in multi-line statement", [$lineNum, 0]);
                }
                $continued = 0;
            }

            while ($pos < $max) {
                if (preg_match('~' . $pseudoTokenRe . '~AsDu', $line, $match, PREG_OFFSET_CAPTURE, $pos)) {
                    /** @var int $start $start */
                    $start = $match[1][1];
                    $end = $start + mb_strlen($match[1][0]);
                    $spos = [$lineNum, $start];
                    $epos = [$lineNum, $end];
                    $pos = $end;
                    if ($start === $end) {
                        continue;
                    }
                    $token = mb_substr($line, $start, $end - $start);
                    $initial = mb_substr($line, $start, 1);

                    if (str_contains($numChars, $initial) || ($initial === '.' && $token != '.' && $token != '...')) {
                        yield new Token(TokenType::NUMBER, $token, $spos, $epos, $line);
                    } elseif (str_contains("\r\n", $initial)) {
                        if ($parenLevel > 0) {
                            yield new Token(TokenType::NL, $token, $spos, $epos, $line);
                        } else {
                            yield new Token(TokenType::NEWLINE, $token, $spos, $epos, $line);
                        }
                    } elseif ($initial == '#') {
                        Must::beTruthy(!str_ends_with($token, "\n"));
                        yield new Token(TokenType::COMMENT, $token, $spos, $epos, $line);
                    } elseif (in_array($token, $tripleQuoted)) {
                        $endProgRe = $endPatterns[$token];
                        if (preg_match('~' . $endProgRe . '~AsDu', $line, $match, PREG_OFFSET_CAPTURE, $pos))  { # all on one line
                            if (count($match) !== 1) {
                                throw new UnexpectedValueException();
                            }
                            $pos = mb_strlen($match[0]);
                            $token = mb_substr($line, $start, $end - $start);
                            yield new Token(TokenType::STRING, $token, $spos, [$lineNum, $pos], $line);
                        } else {
                            $strStart = [$lineNum, $start]; # multiple lines
                            $contStr = mb_substr($line, $start);
                            $contLine = $line;
                            break;
                        }
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
                    } elseif (in_array($initial, $singleQuoted) || in_array(mb_substr($token, 0, 2), $singleQuoted) || in_array(mb_substr($token, 0, 3), $singleQuoted)) {
                        if (str_ends_with($token, "\n")) { # continued string
                            $strStart = [$lineNum, $start];
                            # Again, using the first 3 chars of the
                            #  token. This is looking for the matching end
                            #  regex for the correct type of quote
                            #  character. So it's really looking for
                            #  endpats["'"] or endpats['"'], by trying to
                            #  skip string prefix characters, if any.
                            if (isset($endPatterns[$initial])) {
                                $endProgRe = $endPatterns[$initial];
                            } elseif (isset($endPatterns[$token[1]])) {
                                $endProgRe = $endPatterns[$token[1]];
                            } elseif (isset($endPatterns[$token[2]])) {
                                $endProgRe = $endPatterns[$token[2]];
                            } else {
                                throw new UnexpectedValueException();
                            }
                            $contStr = mb_substr($line, $start);
                            $needCont = 1;
                            $contLine = $line;
                            break;
                        } else {
                            yield new Token(TokenType::STRING, $token, $spos, $epos, $line); # ordinary string
                        }
                    } elseif (GeneralTokenizerRe::isIdentifier($initial)) { # ordinary name
                        yield new Token(TokenType::NAME, $token, $spos, $epos, $line);
                    } elseif ($initial == '\\') { # continued stmt
                        $continued = 1;
                    }
                    else {
                        if (str_contains('([{', $initial)) {
                            $parenLevel++;
                        } elseif (str_contains(')]}', $initial)) {
                            $parenLevel--;
                        }
                        yield new Token(TokenType::OP, $token, $spos, $epos, $line);
                    }
                } else {
                    yield new Token(TokenType::ERRORTOKEN, $line[$pos], [$lineNum, $pos], [$lineNum, $pos + 1], $line);
                    $pos++;
                }
            }
        }
        // Add an implicit NEWLINE if the input doesn't end in one
        if (false !== $lastLine && strlen($lastLine) && !(str_ends_with($lastLine, "\r") || str_ends_with($lastLine, "\n") || str_ends_with($lastLine, "\r\n")) && !str_starts_with(trim($lastLine), '#')) {
            yield new Token(TokenTYpe::NEWLINE, '', [$lineNum - 1, mb_strlen($lastLine)], [$lineNum - 1, mb_strlen($lastLine) + 1], '');
        }
        foreach (array_slice($indents, 1) as $_) { // pop remaining indent levels
            yield new Token(TokenType::DEDENT, '', [$lineNum, 0], [$lineNum, 0], '');
        }
        yield new Token(TokenType::ENDMARKER, '', [$lineNum, 0], [$lineNum, 0], '');
        if (!feof($stream)) {
            throw new RuntimeException('Unexpected end of the stream');
        }

        fclose($stream);
    }

    public static function untokenize() {
        throw new NotImplementedException(__METHOD__);
    }
}