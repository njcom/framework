<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use Stringable;

use function debug_backtrace;
use function dirname;
use function implode;

class Trace implements Stringable {
    protected array $frames = [];

    public function __construct() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $this->frames = [];
        foreach ($trace as $frame) {
            if (isset($frame['file']) && dirname($frame['file']) == __DIR__) {
                continue;
            }
            $this->frames[] = $this->normalizeFrame($frame);
        }
    }

    protected static function normalizeFrame(array $frame): Frame {
        $function = null;
        if (isset($frame['function'])) {
            $function = $frame['function'] . '()';
        }
        if (isset($frame['class']) && isset($frame['type'])) {
            $function = $frame['class'] . $frame['type'] . $function;
        }
        return new Frame(
            [
                'function' => $function,
                'filePath' => $frame['file'] ?? null,
                'line'     => $frame['line'] ?? null,
            ]
        );
    }

    public function __toString(): string {
        $lines = [];
        foreach ($this->frames as $index => $frame) {
            $lines[] = '#' . $index . ' ' . $frame;
        }

        return implode("\n", $lines);
    }

    public function toArr(): array {
        return $this->frames;
    }
}
