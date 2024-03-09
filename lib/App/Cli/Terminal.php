<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Cli;

use const Morpho\Base\INDENT;
use const STDERR;
use const STDOUT;

class Terminal {
    public const ITEM_PREFIX = '- ';
    public const INDENT = INDENT;

    /**
     * @var resource
     */
    protected $stdout;
    /**
     * @var resource
     */
    private $stderr;

    public function __construct() {
        $this->stdout = STDOUT ?? fopen('php://output', 'w');
        $this->stderr = STDERR ?? fopen('php://stderr', 'w');
    }

    public function write(string $text, bool $newLn = true): void {
        fwrite($this->stdout, $text . ($newLn ? "\n" : ''));
        fflush($this->stdout);
    }

    public function writeError(string $text, bool $newLn = true): void {
        fwrite($this->stderr, $text . ($newLn ? "\n" : ''));
        fflush($this->stderr);
    }

    public static function showList(iterable $it, int $indentMultiplier = 0, string $itemPrefix = self::ITEM_PREFIX): void {
        foreach (self::simplifyIter($it, $indentMultiplier, $itemPrefix) as $item) {
            showLine($item);
        }
    }

    public static function renderList(iterable $struct, int $indentMultiplier = 0, string $itemPrefix = self::ITEM_PREFIX): string {
        return implode("\n", iterator_to_array(self::simplifyIter($struct, $indentMultiplier, $itemPrefix), false));
    }

    public static function isInteractive(): bool {
        return posix_isatty(STDOUT);
    }

    public static function fzfChoice(iterable $items, string $prompt = null): false|string {
        if (!is_array($items)) {
            /** @var \Traversable $items */
            $items = iterator_to_array($items, false);
        }
        $fpSpec = [
            0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
            //2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
        ];
        $fp = proc_open('fzf --tac --no-sort' . (null !== $prompt ? ' --prompt=' . escapeshellarg($prompt . ': ') : '') . ' --tabstop=4 --cycle', $fpSpec, $pipes);
        if (!$fp) {
            throw new \RuntimeException("Unable to get choice");
        }
        /** @var array $items */
        fwrite($pipes[0], implode("\n", $items));
        fclose($pipes[0]);
        $choice = trim(stream_get_contents($pipes[1]));
        fclose($pipes[1]);
        proc_close($fp);
        if (!strlen($choice)) {
            return false;
        }
        return $choice;
    }

    private static function renderPrefix(string $itemPrefix, int $indentMultiplier): string {
        return str_repeat(self::INDENT, $indentMultiplier) . $itemPrefix;
    }

    /**
     * @TODO: Write tests
     */
    private static function simplifyIter(iterable $iter, int $indentMultiplier = 0, string $itemPrefix = self::ITEM_PREFIX): iterable {
        foreach ($iter as $key => $items) {
            if (!is_numeric($key)) {
                yield self::renderPrefix($itemPrefix, $indentMultiplier) . $key . ':';
                if (is_iterable($items)) {
                    yield from self::simplifyIter($items, $indentMultiplier + 1, $itemPrefix);
                } else {
                    yield self::renderPrefix($itemPrefix, $indentMultiplier + 1) . $items;
                }
            } else {
                if (is_iterable($items)) {
                    yield from self::simplifyIter($items, $indentMultiplier, $itemPrefix);
                } else {
                    yield self::renderPrefix($itemPrefix, $indentMultiplier) . $items;
                }
            }
        }
    }
}

