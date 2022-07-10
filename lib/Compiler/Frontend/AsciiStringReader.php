<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

/**
 * Based on [StringScanner in Ruby](https://docs.ruby-lang.org/en/3.0.0/StringScanner.html), see [license](https://github.com/ruby/ruby/blob/master/COPYING)
 */
class AsciiStringReader implements IStringReader {
    protected string $input;
    protected int $offset = 0;
    protected int $prevOffset = 0;
    protected ?string $matched = null;
    protected bool $anchored = true;
    protected ?array $subgroups = null;

    /**
     * @param string $input
     * @param bool Either use the `A` PCRE modifier (PCRE_ANCHORED) for all regular expressions or not.
     */
    public function __construct(string $input, bool $anchored = true) {
        $this->input = $input;
        $this->anchored = $anchored;
    }

    public function setInput(string $input): void {
        $this->input = $input;
        $this->reset();
    }

    public function reset(): void {
        $this->matched = null;
        $this->offset = $this->prevOffset = 0;
        $this->subgroups = null;
    }

    public function input(): string {
        return $this->input;
    }

    public function concat(string $input): void {
        $this->input .= $input;
    }

    public function setOffset(int $offset): void {
        $this->offset = $offset;
    }

    public function offset(): int {
        return $this->offset;
    }

    public function check(string $re): ?string {
        return $this->read($re, false);
    }

    public function read(string $re, bool $advanceOffset = true, bool $returnStr = true): string|int|null {
        $matched = null;
        if (preg_match($this->re($re), $this->input, $match, 0, $this->offsetInBytes())) {
            $matched = $match[0];
            if ($advanceOffset) {
                $this->prevOffset = $this->offset;
                $this->offset += $this->strlen($matched);
            }
        }
        $this->matched = $matched;
        $this->subgroups = null === $matched ? null : $match;
        if ($returnStr) {
            return $matched;
        }
        return $matched === null ? null : $this->strlen($matched);
    }

    public function offsetInBytes(): int {
        return $this->offset;
    }

    public function skip(string $re): ?int {
        return $this->read($re, true, false);
    }

    public function look(string $re): ?int {
        return $this->read($re, false, false);
    }

    public function checkUntil(string $re): ?string {
        return $this->readUntil($re, false);
    }

    public function readUntil(string $re, bool $advanceOffset = true, bool $returnStr = true): string|int|null {
        if (preg_match($this->re($re, false), $this->input, $match, PREG_OFFSET_CAPTURE, $this->offsetInBytes())) {
            $res = $this->substr(
                $this->input,
                $this->offset,
                $match[0][1] - $this->offset + $this->strlen($match[0][0])
            );
            if ($advanceOffset) {
                $this->prevOffset = $match[0][1];
                $this->offset += $this->strlen($res);
            }
            $this->subgroups = array_column($match, 0);
            $this->matched = $match[0][0];
            if ($returnStr) {
                return $res;
            }
            return $this->strlen($res);
        }
        $this->subgroups = null;
        return $this->matched = null;
    }

    public function skipUntil(string $re): ?int {
        return $this->readUntil($re, true, false);
    }

    public function lookUntil(string $re): ?int {
        return $this->readUntil($re, false, false);
    }

    public function char(): ?string {
        $this->subgroups = $this->matched = null;
        if ($this->offset >= $this->strlen($this->input)) {
            return null;
        }
        $this->prevOffset = $this->offset;
        $matched = $this->substr($this->input, $this->offset, 1);
        $this->offset += $this->strlen($matched);
        $this->subgroups = [$matched];
        return $this->matched = $matched;
    }

    public function unread(): void {
        if (null === $this->matched) {
            throw new StringReaderException("Previous match record doesn't exist");
        }
        $this->matched = null;
        $this->subgroups = null;
        $this->offset = $this->prevOffset;
    }

    public function peek(int $n): string {
        $res = $this->substr($this->input, $this->offset, $n);
        if (false !== $res) {
            return $res;
        }
        return '';
    }

    public function terminate(): void {
        $this->matched = null;
        $this->subgroups = null;
        $this->offset = $this->strlen($this->input);
    }

    public function isLineStart(): bool {
        if ($this->offset == 0) {
            return true;
        }
        $n = strlen($this->input);
        $offsetInBytes = $this->offsetInBytes();
        return $offsetInBytes < $n
            && ($this->input[$offsetInBytes - 1] == "\n" // *nix
                || $this->input[$offsetInBytes - 1] == "\r" // mac
                || ($n >= 2 && $this->input[$offsetInBytes - 2] == "\r" && $this->input[$offsetInBytes - 1] == "\n")); // win
    }

    public function isEnd(): bool {
        return $this->offset >= $this->strlen($this->input);
    }

    public function matched(): ?string {
        return $this->matched;
    }

    public function matchedSize(): ?int {
        return null === $this->matched || $this->offset >= $this->strlen($this->input)
            ? null
            : $this->strlen($this->matched);
    }

    public function subgroups(): ?array {
        return $this->subgroups;
    }

    public function preMatch(): ?string {
        return null === $this->matched
            ? null
            : $this->substr($this->input, 0, $this->prevOffset);
    }

    public function postMatch(): ?string {
        return null === $this->matched
            ? null
            : $this->substr($this->input, $this->offset, null);
    }

    public function rest(): string {
        $res = $this->substr($this->input, $this->offset, null);
        if (false === $res) {
            return '';
        }
        return $res;
    }

    public function restSize(): int {
        return $this->strlen($this->input) - $this->offset;
    }

    public function isAnchored(): bool {
        return $this->anchored;
    }

    protected function substr(string $s, int $offset, ?int $length): string {
        return substr($s, $offset, $length);
    }

    protected function strlen(mixed $s): int {
        return strlen($s);
    }

    protected function re(string $re, bool $anchored = null): string {
        if (null === $anchored) {
            return $this->anchored ? $re . 'A' : $re;
        }
        return $anchored ? $re . 'A' : $re;
    }
}
