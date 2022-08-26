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
class MbStringReader extends AsciiStringReader {
    protected string $encoding;

    /**
     * @param string $input
     * @param string|null $encoding
     * @param bool Either use the `A` PCRE modifier (PCRE_ANCHORED) for all regular expressions or not.
     */
    public function __construct(string $input, string $encoding = null, bool $anchored = true) {
        parent::__construct($input, $anchored);
        $this->encoding = null === $encoding ? 'utf-8' : $encoding;
    }

    public function offsetInBytes(): int {
        return strlen($this->substr($this->input, 0, $this->offset));
    }

    protected function re(string $re, bool $anchored = null): string {
        if (null === $anchored) {
            return $this->anchored ? $re . 'Au' : $re;
        }
        return $anchored ? $re . 'Au' : $re;
    }

    protected function substr(string $s, int $offset, ?int $length): string {
        return mb_substr($s, $offset, $length);
    }

    protected function strlen(mixed $s): int {
        return mb_strlen($s, $this->encoding);
    }
}