<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use ErrorException;
use Exception;
use Generator;
use Morpho\Base\NotImplementedException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;

use function array_pop;
use function array_shift;
use function bin2hex;
use function error_reporting;
use function file_put_contents;
use function filesize;
use function func_get_arg;
use function func_num_args;
use function htmlspecialchars;
use function is_null;
use function is_object;
use function ltrim;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function rtrim;
use function sort;
use function str_repeat;
use function str_replace;
use function stristr;
use function substr_count;
use function trim;
use function var_dump;
use function var_export;

/**
 * Utility class to debug any PHP application.
 * To debug applications, consider to add/change the following php.ini settings:
 *     html_errors = 0
 */
class Debugger {
    private static $instance;
    private static $class;
    protected array $ignoredFrames = [];
    protected ?bool $isHtmlMode = null;
    private int $exitCode = 1;

    protected function __construct() {
    }

    final public static function instance() {
        if (null === self::$instance) {
            self::$instance = self::$class ? new self::$class : new self();
        }

        return self::$instance;
    }

    final public static function resetState() {
        self::$instance = null;
    }

    public static function setClass($class) {
        self::$class = $class;
    }

    public function type($obj): void {
        $this->dump(get_debug_type($obj));
    }

    public function dump(): void {
        $argsCount = func_num_args();
        $output = '';
        for ($i = 0; $i < $argsCount; $i++) {
            $var = func_get_arg($i);
            $output .= $this->varToStr($var);
        }
        $output .= $this->calledAt();
        if ($this->isHtmlMode()) {
            $output = $this->formatHtml($output);
        }
        echo $output;
        exit($this->exitCode);
    }

    public function varToStr($var, bool $fixOutput = true): string {
        ob_start();
        if ($var instanceof Generator) {
            var_dump("\\Generator which yields the values:\n" . $this->describeGen($var));
        } else {
            var_dump($var);
        }
        $output = trim(ob_get_clean());
        if ($fixOutput) {
            $output = preg_replace('~]=>\\n\s*~si', '] => ', $output);
        }
        return $this->formatLine($output);
    }

    protected function describeGen(Generator $val): string {
        $out = '';
        $i = 0;
        foreach ($val as $key => $value) {
            $out .= rtrim($this->describeVal($key), ';') . ' => ' . rtrim($this->describeVal($value)) . "\n";
            if ($i > 100) {
                $out .= "...\n";
                break;
            }

            $i++;
        }
        return $out;
    }

    protected function describeVal($val, bool $stripNumericKeys = true): string {
        $res = preg_replace(
                [
                    '~=>\s+array~si',
                    '~array \(~si',
                ],
                [
                    '=> array',
                    'array(',
                ],
                var_export($val, true)
            ) . ';';
        if ($stripNumericKeys) {
            $res = preg_replace('~^(\s+)\d+.*=> ~mi', '\\1', $res);
        }
        // Reindent code: replace 2 spaces -> 4 spaces.
        $res = preg_replace_callback(
            '~^\s+~m',
            function ($match) {
                $count = substr_count($match[0], '  ');
                return str_repeat('  ', $count * 2);
            },
            $res
        );
        return $res;
    }

    protected function formatLine($line): string {
        $line = (string) $line;
        if (!$this->isHtmlMode()) {
            return "\n$line\n";
        }
        return '<pre>' . htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE) . '</pre>';
    }

    public function isHtmlMode(bool $flag = null): bool {
        if (null !== $flag) {
            $this->isHtmlMode = $flag;
        } elseif (null === $this->isHtmlMode) {
            if (PHP_SAPI == 'cli') {
                $this->isHtmlMode = false;
            } else {
                $acceptsHtml = isset($_SERVER['HTTP_ACCEPT'])
                    && false !== stristr($_SERVER['HTTP_ACCEPT'], 'text/html');
                $this->isHtmlMode = $acceptsHtml;// && !ini_get('html_errors')
            }
        }
        return $this->isHtmlMode;
    }

    public function calledAt(): string {
        $frame = $this->findCallerFrame();
        if (null === $frame) {
            return '—';
        }
        return $this->formatLine("Debugger called at [{$frame['filePath']}:{$frame['line']}]");
    }

    protected function findCallerFrame(): ?Frame {
        // @TODO: Move isIgnoredFrame to Trace::ignoreFrame(), then call Trace::toArr()
        $trace = (new Trace())->toArr();
        do {
            $frame = array_shift($trace);
        } while ($frame && (!isset($frame['line']) || $this->isIgnoredFrame($frame)));

        return $frame;
    }

    /**
     * @TODO: Move to Trace.
     *
     * @param Frame $frame
     * @return bool
     */
    protected function isIgnoredFrame(Frame $frame): bool {
        foreach ($this->ignoredFrames as $frameToIgnore) {
            if ($frame['filePath'] == $frameToIgnore['filePath']
                && (is_null($frameToIgnore['line']) || $frame['line'] == $frameToIgnore['line'])
            ) {
                return true;
            }
        }
        return false;
    }

    protected function formatHtml($output, $wrapOutput = true): string {
        $output = str_replace(
            '<pre>',
            '<pre style="margin: 1.5em; padding: 0; font-weight: bold; color: #333;">',
            $output
        );
        if ($wrapOutput) {
            $html = <<<'OUT'
<script>
window.onload = function () {
    var bodyNode = document.getElementsByTagName('body')[0];
    var nodes = bodyNode.getElementsByTagName('*');
    var debuggerNode;
    for (var i = nodes.length; i--;) {
        var node = nodes.item(i);
        if (node.hasAttribute('id') && node.getAttribute('id') == 'morpho-debugger') {
            debuggerNode = node.cloneNode(true);
        }
    }
    bodyNode.innerHTML = '';
    bodyNode.appendChild(debuggerNode);
};
</script>
<div id="morpho-debugger" style="position: absolute; top: 0; left: 0; right: 0; background: #fff; color: #000; text-align: left;">
    <div style="border: solid 2px #3CB371; border-radius: 5px; margin: 1em; overflow: auto;">
        <h1 style="margin: 0; padding: .2em; background: #3CB371; color: #fff; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;">Debugger</h1>
        {{output}}
    </div>
</div>
OUT;
            return str_replace(['{{output}}'], $output, $html);
        }

        return $output;
    }

    public function dumpWithExitCode(...$args): void {
        $this->setExitCode(array_pop($args))
            ->dump(...$args);
    }

    public function setExitCode(int $exitCode): static {
        $this->exitCode = $exitCode;
        return $this;
    }

    public function dumpBytes(...$args): void {
        $output = '';
        foreach ($args as $arg) {
            $bytesFormatted = bin2hex($arg);
            $output .= $this->varToStr($bytesFormatted);
        }
        $output .= $this->calledAt();
        if ($this->isHtmlMode()) {
            $output = $this->formatHtml($output, false);
        }
        echo $output;
        exit($this->exitCode);
    }

    /**
     * Taken from https://stackoverflow.com/questions/1057572/how-can-i-get-a-hex-dump-of-a-string-in-php
     */
    public function dumpHex(string $bytes): void {
        static $from = '';
        static $to = '';

        static $width = 16; # number of bytes per line

        static $pad = '.'; # padding for non-visible characters

        if ($from === '') {
            for ($i = 0; $i <= 0xFF; $i++) {
                $from .= chr($i);
                $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
            }
        }

        $hex = str_split(bin2hex($bytes), $width * 2);
        $chars = str_split(strtr($bytes, $from, $to), $width);

        $offset = 0;
        foreach ($hex as $i => $line) {
            echo sprintf('%6X', $offset) . ' : ' . implode(' ', str_split($line, 2)) . ' [' . $chars[$i] . ']' . "\n";
            $offset += $width;
        }
    }

    public function trace(): void {
        $output = $this->traceToStr();
        if ($this->isHtmlMode()) {
            $output = $this->formatHtml($output);
        }
        echo $output;
        exit($this->exitCode);
    }

    public function traceToStr(): string {
        return $this->formatLine(new Trace());
    }

    /**
     * @param string|object $object An object for that methods will be dumped.
     * @param int $filter Logical combination of the following constants:
     *                              ReflectionMethod::IS_STATIC
     *                              ReflectionMethod::IS_PUBLIC
     *                              ReflectionMethod::IS_PROTECTED
     *                              ReflectionMethod::IS_protected
     *                              ReflectionMethod::IS_ABSTRACT
     *                              ReflectionMethod::IS_FINAL
     * @param string|null $regexp
     * @param bool $sort Either sort result or not
     */
    public function methods($object, $filter = null, $regexp = null, $sort = false) {
        if (null === $filter) {
            $filter = ReflectionMethod::IS_PUBLIC;
        }
        $r = is_object($object) ? new ReflectionObject($object) : new ReflectionClass($object);
        $methods = [];
        foreach ($r->getMethods($filter) as $method) {
            if (null !== $regexp) {
                if (!preg_match($regexp, $method->getName())) {
                    continue;
                }
            }
            // @TODO: Add arguments list.
            $methods[] = $method->getName() . '()';
        }
        if ($sort) {
            sort($methods);
        }
        $this->dump($methods);
    }

    /**
     * Improved version of the var_export
     */
    public function varExport($var, bool $return = false, bool $stripNumericKeys = true) {
        $out = $this->describeVal($var, $stripNumericKeys);

        $output = $this->formatLine($out)
            . $this->calledAt();

        if ($this->isHtmlMode()) {
            $output = $this->formatHtml($output);
        }

        if ($return) {
            return $output;
        }
        echo $output;
    }

    public function logToFile(string $filePath, ...$args) {
        $oldHtmlMode = $this->isHtmlMode;
        $this->isHtmlMode(false);
        ob_start();
        $this->varDump(...$args);
        $content = ob_get_clean();
        if (@filesize($filePath) == 0) {
            $content = ltrim($content);
        }
        $result = @file_put_contents($filePath, $content, FILE_APPEND);
        $this->isHtmlMode = $oldHtmlMode;
        return $result !== false;
    }

    public function varDump() {
        $argsCount = func_num_args();
        $output = '';
        for ($i = 0; $i < $argsCount; $i++) {
            $var = func_get_arg($i);
            $output .= $this->varToStr($var);
        }
        $output .= $this->calledAt();
        if ($this->isHtmlMode()) {
            $output = $this->formatHtml($output, false);
        }
        echo $output;
    }

    public function on(callable $errorHandlerCallback = null, int $errorLevel = null) {
        throw new NotImplementedException();
        /*
        $this->oldDisplayErrors = ini_set('display_errors', 1);

        if (null === $errorHandlerCallback) {
            $errorHandlerCallback = array($this, 'errorHandler');
        }
        if (!\is_callable($errorHandlerCallback)) {
            throw new \InvalidArgumentException('Invalid callback was provided.');
        }
        if (null === $errorLevel) {
            $errorLevel = E_ALL | E_STRICT;
        }
        $oldErrorHandler = set_error_handler($errorHandlerCallback, $errorLevel);
        if (null !== $oldErrorHandler) {
            \array_push($this->oldErrorHandlers, $oldErrorHandler);
        }

        \array_push($this->oldErrorLevels, error_reporting($errorLevel));
        */
    }

    public function off() {
        throw new NotImplementedException();
        /*
        if (null !== $this->oldDisplayErrors) {
            ini_set('display_errors', )
        }
        if (count($this->oldErrorLevels) > 0) {
            error_reporting(array_pop($this->oldErrorLevels));
        }

        if (count($this->oldErrorHandlers) > 0) {
            set_error_handler(array_pop($this->oldErrorHandlers));
        }
        */
    }

    public function ignoreCaller(string $filePath, int $lineNumber = null): static {
        $this->ignoredFrames[] = ['filePath' => $filePath, 'line' => $lineNumber];
        return $this;
    }

    protected function __clone() {
    }

    protected function formatLines(array $lines): string {
        $output = '';
        foreach ($lines as $line) {
            $output .= $this->formatLine($line);
        }
        return $output;
    }

    protected function errorHandler($level, $message, $filePath, $line, $context) {
        if ($level & error_reporting()) {
            try {
                // @TODO: Sync with PHP >= 7.
                $types = [
                    E_ERROR             => 'Error',
                    E_WARNING           => 'Warning',
                    E_PARSE             => 'Parse error',
                    E_NOTICE            => 'Notice',
                    E_CORE_ERROR        => 'Core error',
                    E_CORE_WARNING      => 'Core warning',
                    E_COMPILE_ERROR     => 'Compile error',
                    E_COMPILE_WARNING   => 'Compile warning',
                    E_USER_ERROR        => 'User error',
                    E_USER_WARNING      => 'User warning',
                    E_USER_NOTICE       => 'User notice',
                    E_STRICT            => 'Strict warning',
                    E_RECOVERABLE_ERROR => 'Recoverable fatal error',
                    E_DEPRECATED        => 'Deprecated notice',
                ];
                $message = $types[$level] . ': ' . $message;

                // Hack to get informative backtrace.
                throw new ErrorException($message, 0, $level, $filePath, $line);
            } catch (Exception $e) {
                $this->dump($e->__toString());
            }
        }
    }
}
