<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

/**
 * String reader can be useful in recursrive descent parsers.
 * Based on [stringscanner from ruby](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html), see [license](https://github.com/ruby/ruby/blob/master/COPYING)
 */
interface IStringReader {
    /**
     * Sets the new input.
     * Modifies: offset, match, subgroups
     * Ruby method: [string=()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-string-3D).
     * @param string $input The new input string.
     * @return void
     */
    public function setInput(string $input): void;

    /**
     * Ruby method: [string()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-string).
     * @return string The current input string.
     */
    public function input(): string;

    /**
     * Appends $input to the current input.
     * @param string $input
     * @return void
     */
    public function concat(string $input): void;

    /**
     * Sets the new offset (modifies it) in characters.
     * @param int $offset The new offset in characters.
     * @return void
     */
    public function setOffset(int $offset): void;

    /**
     * Returns the current offset in characters.
     * @return int The current offset in characters.
     */
    public function offset(): int;

    /**
     * Return the current offset in bytes.
     * Ruby method: [pos()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-pos).
     * @return int The current offset in bytes.
     */
    public function offsetInBytes(): int;

    /**
     * Checks what `read()` will read.
     * Modifes: match, subgroups
     * @param string $re Pattern (PCRE) to match.
     * @return string|null The match substring or null if there is no match.
     */
    public function check(string $re): ?string;

    /**
     * Checks what `readUntil()` will read.
     * Modifies: match, sugroups
     * @param string $re Pattern (PCRE) to match.
     * @return string|null The match substring from the current offset up to and including the end of the match or null otherwise.
     */
    public function checkUntil(string $re): ?string;

    /**
     * Reads the text (input) matching the pattern.
     * Modifies: offset, match, subgroups
     * Ruby methods:
     *     [scan()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-scan).
     *     [scan_full()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-scan_full)
     * @param string $re Pattern (PCRE) to match.
     * @return string|null The match substring or null if there is no match.
     */
    public function read(string $re): string|null;

    /**
     * Reads the text until the pattern is match.
     * Modifies: offset, match, subgroups
     * Ruby methods:
     *     [scan_until()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-scan_until).
     *     [search_full()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-search_full).
     * @param string $re Pattern (PCRE) to match.
     * @return string|null
     */
    public function readUntil(string $re): string|null;

    /**
     * Tests whether the given pattern is match from the current scan pointer.
     * Modifies: match, subgroups
     * Ruby method: [match?()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-match-3F)
     * @param string $re Pattern (PCRE) to match.
     * @return int|null The length of the match, or null.
     */
    public function look(string $re): ?int;

    /**
     * Looks ahead to see if the pattern exists anywhere in the string.
     * Modifies: match, subgroups
     * Ruby method: [exist?()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-exist-3F).
     * @param string $re Pattern (PCRE) to match.
     * @return int|null
     */
    public function lookUntil(string $re): ?int;

    /**
     * Skips the matching bytes from the current offset.
     * Modifies: offset, match, subgroups
     * @param string $re Pattern (PCRE) to match.
     * @return int|null The number of match bytes or null in case of no matching.
     */
    public function skip(string $re): ?int;

    /**
     * Skips the text until the pattern is match.
     * Modifies, offset, match, subgroups
     * @param string $re Pattern (PCRE) to match.
     * @return int|null
     */
    public function skipUntil(string $re): ?int;

    /**
     * Returns a string with length $n from the current offset.
     * @param int $n
     * @return string
     */
    public function peek(int $n): string;

    /**
     * Reads the next character.
     * Modifies: charOffset, match
     * Ruby methods:
     *     * [getch()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-getch)
     * @return string|null
     *     * [get_byte()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-get_byte).
     */
    public function char(): ?string;

    /**
     * Changes the offset to the previous one. Only one previous offset is remembered
     * Modifies: offset, match, subgroups
     * Ruby method: [unscan()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-unscan).
     * @return void
     */
    public function unread(): void;

    /**
     * Sets the offset to the end of the string.
     * Modifies: offset, match, subgroups
     */
    public function terminate(): void;

    /**
     * Resets the offset to 0.
     * Modifies: offset, match, subgroups
     * @return void
     */
    public function reset(): void;

    /**
     * Check either the current offset is at the start of any line.
     * Ruby method: [beginning_of_line()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-beginning_of_line-3F).
     * @return bool `true` If the offset is at the start of any line.
     */
    public function isLineStart(): bool;

    /**
     * Checks either the current offset is >= the current input string length or not.
     * Ruby method: [eos()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-eos-3F)
     * @return bool `true` If the offset is at the end of the input string.
     */
    public function isEnd(): bool;

    /**
     * Returns the `match` register: the last match string.
     * @return string|null
     */
    public function match(): ?string;

    /**
     * Returns size of `match` register in characters.
     * Ruby method: [matched_size()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-matched_size)
     * @return int|null
     */
    public function matchLen(): ?int;

    /**
     * Returns the part of input string before the `match` register.
     * @return string|null
     */
    public function preMatch(): ?string;

    /**
     * Returns the part of input string after the `match` register.
     * @return string|null
     */
    public function postMatch(): ?string;

    /**
     * Returns subgroups for the last match including the full match.
     * Ruby methods:
     *     [size()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-size)
     *     [values_at()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-values_at)
     *     [captures()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-captures)
     * @return array|null
     */
    public function subgroups(): ?array;

    /**
     * Returns the “rest” of the string (i.e. everything after the scan pointer). If there is no more data (eos? = true), it returns "".
     * @return string
     */
    public function rest(): string;

    /**
     * Ruby method:[rest_size()](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html#method-i-rest_size)
     * @return int
     */
    public function restLen(): int;

    public function isAnchored(): bool;
}