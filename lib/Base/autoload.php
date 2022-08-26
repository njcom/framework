<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
/**
 * Some functions are based on functions found at [nikic/iter](https://github.com/nikic/iter) package, Copyright (c) 2013 by Nikita Popov
 */
namespace Morpho\Base;

use ArrayObject;
use Closure;
use Generator;
use InvalidArgumentException;
use OutOfBoundsException;
use RuntimeException;
use Stringable;
use Throwable;
use Traversable;
use UnexpectedValueException;

use function array_fill_keys;
use function array_key_exists;
use function array_merge;
use function array_pop;
use function array_shift;
use function array_slice;
use function array_values;
use function count;
use function extract;
use function floatval;
use function func_num_args;
use function get_object_vars;
use function htmlspecialchars;
use function htmlspecialchars_decode;
use function in_array;
use function is_array;
use function is_iterable;
use function is_string;
use function json_decode;
use function json_encode;
use function lcfirst;
use function md5;
use function number_format;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function ord;
use function pow;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function preg_replace_callback;
use function preg_split;
use function round;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function strtoupper;
use function strtr;
use function substr;
use function trim;
use function ucwords;
use function usleep;

use const PREG_SPLIT_NO_EMPTY;

const TRIM_CHARS = " \t\n\r\x00\x0B";

// Matches EOL character:
const EOL_RE = '(?>\r\n|\n|\r)';
const EOL_FULL_RE = '~' . EOL_RE . '~s';

const INDENT_SIZE = 4; // size in spaces
define(__NAMESPACE__ . '\\INDENT', str_repeat(' ', INDENT_SIZE));

const SHORTEN_TAIL = '...';
const SHORTEN_LENGTH = 30;

const TPL_FILE_EXT = '.php.tpl';

// https://stackoverflow.com/questions/23837286/why-does-php-not-provide-an-epsilon-constant-for-floating-point-comparisons
// Can be used in comparison operations with real numbers.
const EPS = 0.00001;

const WAIT_INTERVAL_MICRO_SEC = 200000;

/**
 * @psalm-type List = iterable|string|Stringable
 */

/**
 * @param mixed $val
 * @return bool Returns true if the $val is one of `enum` `case` values.
 */
function isEnumCase(mixed $val): bool {
    return is_object($val) && enum_exists($val::class);
}

function caseVal(mixed $val): mixed {
    return isEnumCase($val) ? $val->value : $val;
}

function enumVals(string $enumName, bool $preserveNames = true): array {
    $vals = [];
    foreach ($enumName::cases() as $case) {
        $vals[$case->name] = $case->value;
    }
    if (!$preserveNames) {
        return array_values($vals);
    }
    return $vals;
}

/**
 * @psalm-param callable(mixed, mixed): bool $predicate
 * @psalm-param List $list
 * @return bool
 */
function all(callable $predicate, iterable|string|Stringable $list): bool {
    foreach (toIt($list) as $key => $val) {
        if (!$predicate($val, $key)) {
            return false;
        }
    }
    return true;
}

/**
 * Converts to iterable so that it can be used in foreach loop.
 * @psalm-param List $list
 * @return iterable
 * @psalm-pure
 */
function toIt(iterable|string|Stringable $list): iterable {
    if (is_iterable($list)) {
        return $list;
    }
    if ($list instanceof Stringable) {
        $list = (string) $list;
    }
    return mb_str_split($list);
}


// todo: review functions below #12

function e(string|Stringable|int|float $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES);
}

function de(string|Stringable|int|float $s): string {
    return htmlspecialchars_decode((string) $s, ENT_QUOTES);
}

/**
 * @param string|iterable<mixed, string> ...$messages
 */
function showLn(...$messages): void {
    if (!count($messages)) {
        echo "\n";
    } else {
        foreach ($messages as $message) {
            if (is_iterable($message)) {
                foreach ($message as $msg) {
                    echo $msg . "\n";
                }
            } else {
                echo $message . "\n";
            }
        }
    }
}

function showOk(string|Stringable $msg = null): void {
    showLn('OK' . (null !== $msg ? ': ' . $msg : ''));
}

/**
 * @param IDisposable $disposable
 * @param mixed $val Will be passed to IFn::__invoke()
 * @return mixed
 */
function using(IDisposable $disposable, $val = null) {
    try {
        $result = $disposable($val);
    } finally {
        $disposable->dispose();
    }
    return $result;
}

function unpackArgs(array $args): array {
    return count($args) === 1 && is_array($args[0])
        ? $args[0]
        : $args;
}

/**
 * @param string|Stringable|iterable<mixed, string>|int|float $list
 * @param string                                                 $pre
 * @param string|null                                            $post
 * @return string|array
 */
function wrap(string|Stringable|iterable|int|float $list, string $pre, string $post = null): string|array {
    if (null === $post) {
        $post = $pre;
    }
    if (is_iterable($list)) {
        $r = [];
        foreach ($list as $k => $v) {
            $r[$k] = $pre . $v . $post;
        }
        return $r;
    }
    /** @var string $list */
    return $pre . $list . $post;
}

function wrapFn(string $prefix, string $suffix): Closure {
    return function (string|Stringable|int|float $list) use ($prefix, $suffix) {
        return $prefix . $list . $suffix;
    };
}

function prepend(string|Stringable|iterable|int|float $list, string $prefix): string|array {
    if (is_iterable($list)) {
        $r = [];
        foreach ($list as $k => $v) {
            $r[$k] = $prefix . (string) $v;
        }
        return $r;
    }
    return $prefix . (string) $list;
}

function prependFn(string $prefix): Closure {
    return function (string|Stringable|int|float $list) use ($prefix) {
        return $prefix . $list;
    };
}

function append(string|Stringable|iterable|int|float $list, string $suffix): string|array {
    if (is_iterable($list)) {
        $r = [];
        foreach ($list as $k => $v) {
            $r[$k] = (string) $v . $suffix;
        }
        return $r;
    }
    return (string) $list . $suffix;
}

function appendFn(string $suffix): Closure {
    return function (string|Stringable|int|float $list) use ($suffix) {
        return $list . $suffix;
    };
}

function q(string|Stringable|iterable|int|float $list): string|array {
    return wrap($list, "'");
}

function qq(string|Stringable|iterable|int|float $list): string|array {
    return wrap($list, '"');
}

/**
 * Generates unique name within single HTTP request.
 */
function uniqueName(): string {
    static $uniqueInt = 0;
    return 'unique' . $uniqueInt++;
}

function words(string|Stringable|int $list, int $limit = -1): array {
    $list = (string) $list;
    return preg_split('~\\s+~s', trim($list), $limit, PREG_SPLIT_NO_EMPTY);
}

/**
 * Replaces first capsed letter or underscore with dash and small later.
 * @param mixed $list Allowed string are: /[a-zA-Z0-9_- ]/s. All other characters will be removed.
 * @param string $additionalChars
 * @param bool $trim Either trailing '-' characters should be removed or not.
 * @return string
 */
function dasherize(string|Stringable|int $list, string $additionalChars = '', bool $trim = true) {
    $string = sanitize($list, '-_ ' . $additionalChars, false);
    $string = deleteDups($string, '_ ');
    $search = ['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'];
    $replace = ['\\1-\\2', '\\1-\\2'];
    $result = strtolower(
        preg_replace(
            $search,
            $replace,
            str_replace(
                ['_', ' '],
                '-',
                $string
            )
        )
    );
    if ($trim) {
        return etrim($result, '-');
    }

    return $result;
}

/**
 * Replaces first capsed letter or dash with underscore and small later.
 *
 * @param Stringable|string $s
 * @param bool $trim Either trailing '_' characters should be removed or not.
 *
 * @return string
 */
function underscore(Stringable|string $s, bool $trim = true) {
    $string = sanitize($s, '-_ ', false);
    $string = deleteDups($string, '- ');
    $result = strtolower(
        preg_replace(
            '~([a-z])([A-Z])~s',
            '$1_$2',
            str_replace(
                ['-', ' '],
                '_',
                $string
            )
        )
    );
    if ($trim) {
        return etrim($result, '_');
    }

    return $result;
}

/**
 * Replaces next letter after the allowed character with capital letter.
 * First latter will be always in upper case.
 *
 * @param Stringable|string $s Allowed string are: /[a-zA-Z0-9_- /\\\\]/s.
 *                       All other characters will be removed.
 *                       The '/' will be transformed to '\'.
 *
 * @return string
 */
function classify(string|Stringable $s): string {
    $string = sanitize(str_replace('/', '\\', (string) $s), '-_\\ ');
    if (str_contains($string, '\\')) {
        $string = preg_replace_callback(
            '{\\\\(\w)}si',
            function ($match) {
                return '\\' . strtoupper($match[1]);
            },
            $string
        );
    }
    $string = str_replace(['-', '_'], ' ', $string);
    $string = ucwords($string);
    return str_replace(' ', '', $string);
}

/**
 * Replaces next letter after the allowed character with capital letter.
 * First latter will be in upper case if $lcfirst == true or in lower case if $lcfirst == false.
 *
 * @param Stringable|string $s
 * @param bool $toUpperFirstChar
 * @return string
 */
function camelize(string|Stringable $s, bool $toUpperFirstChar = false): string {
    $string = sanitize($s, '-_ ');
    $string = str_replace(['-', '_'], ' ', $string);
    $string = ucwords($string);
    $string = str_replace(' ', '', $string);
    if (!$toUpperFirstChar) {
        return lcfirst($string);
    }
    return $string;
}

/**
 * Replaces the '_' character with space, works for camelCased strings also:
 * 'camelCased' -> 'camel cased'. Leaves other characters as is.
 * By default applies e() to escape of HTML special characters.
 */
function humanize(string|Stringable|int $list, bool $escape = true) {
    $result = preg_replace_callback(
        '/([a-z])([A-Z])/s',
        function ($m) {
            return $m[1] . ' ' . strtolower($m[2]);
        },
        str_replace('_', ' ', (string ) $list)
    );
    if ($escape) {
        $result = e($result);
    }
    return $result;
}

/**
 * Works like humanize() except makes all words titleized:
 * 'foo bar_baz' -> 'Foo Bar Baz'
 * or only first word:
 * 'foo bar_baz' -> 'Foo bar baz'
 *
 * @param string $list
 * @param bool $ucwords If == true -> all words will be titleized, else only first word will
 *                        titleized.
 * @param bool $escape Either need to apply escaping of HTML special chars?
 *
 * @return string.
 */
function titleize(string|Stringable|int $list, bool $ucwords = true, bool $escape = true): string {
    $result = humanize($list, $escape);
    if ($ucwords) {
        return ucwords($result);
    }

    return \ucfirst($result);
}

function sanitize(string|Stringable|int $list, string $allowedCharacters, bool $deleteDups = true) {
    $regexp = '/[^a-zA-Z0-9' . preg_quote($allowedCharacters, '/') . ']/s';
    $result = preg_replace($regexp, '', (string) $list);
    if ($deleteDups) {
        $result = deleteDups($result, $allowedCharacters);
    }

    return $result;
}

/**
 * extended trim/etrim: modified version of \trim() that removes all characters from the $chars and whitespaces until non of them will be present in the ends of the source string.
 */
function etrim(string|Stringable|iterable|int|float $list, string $chars = null): string|array {
    if (is_array($list)) {
        $r = [];
        foreach ($list as $k => $v) {
            $r[$k] = $v === null ? '' : etrim($v, $chars);
        }
        return $r;
    }
    return trim((string) $list, $chars . TRIM_CHARS);
}

/**
 * Removes duplicated characters from the string.
 *
 * @param Stringable|int|string $list Source string with duplicated characters.
 * @param Stringable|int|string $chars Either a set of characters to use in character class or a reg-exp pattern that must match
 *                               all duplicated characters that must be removed.
 * @param bool $isCharClass
 * @return string                String with removed duplicates.
 */
function deleteDups(string|Stringable|int $list, Stringable|int|string $chars, bool $isCharClass = true) {
    $regExp = $isCharClass
        ? '/([' . preg_quote((string) $chars, '/') . '])+/si'
        : "/($chars)+/si";
    return preg_replace($regExp, '\1', (string) $list);
}

function format($string, array $args, callable $format): string {
    $fromToMap = [];
    foreach ($args as $key => $value) {
        $fromToMap['{' . $key . '}'] = $format($value);
    }
    return strtr($string, $fromToMap);
}

function shorten(string $text, int $length = SHORTEN_LENGTH, $tail = null): string {
    if (strlen($text) <= $length) {
        return $text;
    }
    if (null === $tail) {
        $tail = SHORTEN_TAIL;
    }
    return substr($text, 0, $length - strlen($tail)) . $tail;
}

function normalizeEols(string $list): string {
    return str_replace(["\r\n", "\n", "\r"], "\n", $list);
    /*$res = \preg_replace(EOL_FULL_RE, "\n", $list);
    if (null === $res) {
        throw new RuntimeException("Unable to replace EOLs");
    }
    return $res;*/
}

function toJson(mixed $val, int $flags = null): string {
    if (null === $flags) {
        $flags = -1;
    }
    return json_encode($val, $flags & JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function fromJson(string $json, bool $objectsToArrays = true): mixed {
    $res = json_decode($json, $objectsToArrays);
    if (null === $res) {
        throw new RuntimeException("Invalid JSON or too deep data");
    }
    return $res;
}

/**
 * Sets properties of the object $instance using values from $props
 * @param object      $instance
 * @param iterable $props E.g.: ['myProp1' => 'myVal1', 'myProp2' => 'myVal2'];
 * @return object
 */
function setProps(object $instance, iterable $props): object {
    $assignProps = function ($props) {
        $knownProps = array_fill_keys(array_keys(get_object_vars($this)), true);
        foreach ($props as $name => $value) {
            if (!isset($knownProps[$name])) {
                throw new UnexpectedValueException("Unknown property '$name'");
            }
            $this->$name = $value;
        }
    };
    $assignProps->call($instance, $props);
    return $instance;
}

/**
 * @param string $haystack
 * @param string $needle
 * @param int $offset
 * @return int|false
 */
function lastPos(string $haystack, string $needle, int $offset = 0) {
    if ($needle === '') {
        return $offset >= 0 ? $offset : 0;
    }
    if ($haystack === '') {
        return false;
    }
    return mb_strrpos($haystack, $needle, $offset);
}

/**
 * The name is taken from the `lines` function in Haskell.
 */
function lines(string $text, bool $filterEmpty = true, bool $trim = true): Traversable {
    if ($text === '') {
        return [];
    }
    foreach (preg_split(EOL_FULL_RE, $text, -1, $filterEmpty ? PREG_SPLIT_NO_EMPTY : 0) as $line) {
        if ($trim) {
            $line = trim($line);
        }
        if ($filterEmpty && $line === '') {
            continue;
        }
        yield $line;
    }
}

function capture(callable $fn): string {
    ob_start();
    try {
        $fn();
    } catch (Throwable $e) {
        // Don't output any result in case of Error
        ob_end_clean();
        throw $e;
    }
    return ob_get_clean();
}

function tpl(string $__filePath, array $__vars = null): string {
    extract((array) $__vars, EXTR_SKIP);
    unset($__vars);
    ob_start();
    try {
        require $__filePath;
    } catch (Throwable $e) {
        // Don't output any result in case of Error
        ob_end_clean();
        throw $e;
    }
    return ob_get_clean();
}

/**
 * Modified version of the operator() from the https://github.com/nikic/iter
 * @Copyright (c) 2013 by Nikita Popov.
 */
function op(string $operator, $arg = null): Closure {
    $functions = [
        'instanceof' => function ($a, $b) {
            return $a instanceof $b;
        },
        '*'          => function ($a, $b) {
            return $a * $b;
        },
        '/'          => function ($a, $b) {
            return $a / $b;
        },
        '%'          => function ($a, $b) {
            return $a % $b;
        },
        '+'          => function ($a, $b) {
            return $a + $b;
        },
        '-'          => function ($a, $b) {
            return $a - $b;
        },
        '.'          => function ($a, $b) {
            return $a . $b;
        },
        '<<'         => function ($a, $b) {
            return $a << $b;
        },
        '>>'         => function ($a, $b) {
            return $a >> $b;
        },
        '<'          => function ($a, $b) {
            return $a < $b;
        },
        '<='         => function ($a, $b) {
            return $a <= $b;
        },
        '>'          => function ($a, $b) {
            return $a > $b;
        },
        '>='         => function ($a, $b) {
            return $a >= $b;
        },
        '=='         => function ($a, $b) {
            return $a == $b;
        },
        '!='         => function ($a, $b) {
            return $a != $b;
        },
        '==='        => function ($a, $b) {
            return $a === $b;
        },
        '!=='        => function ($a, $b) {
            return $a !== $b;
        },
        '&'          => function ($a, $b) {
            return $a & $b;
        },
        '^'          => function ($a, $b) {
            return $a ^ $b;
        },
        '|'          => function ($a, $b) {
            return $a | $b;
        },
        '&&'         => function ($a, $b) {
            return $a && $b;
        },
        '||'         => function ($a, $b) {
            return $a || $b;
        },
        '**'         => function ($a, $b) {
            return pow($a, $b);
        },
        '<=>'        => function ($a, $b) {
            return $a == $b ? 0 : ($a < $b ? -1 : 1);
        },
    ];

    if (!isset($functions[$operator])) {
        throw new InvalidArgumentException("Unknown operator \"$operator\"");
    }

    $fn = $functions[$operator];
    if (func_num_args() === 1) {
        // Return a function which expects 2 arguments.
        return $fn;
    } else {
        // Capture the first argument of the binary operator, return a function which expect the second one (partial application and currying).
        return function ($a) use ($fn, $arg) {
            return $fn($a, $arg);
        };
    }
}

function not(callable $predicateFn): Closure {
    return function (...$args) use ($predicateFn) {
        return !$predicateFn(...$args);
    };
}

function partial(callable $fn, ...$args1): Closure {
    return function (...$args2) use ($fn, $args1) {
        return $fn(...array_merge($args1, $args2));
    };
}

/**
 * Returns a new function which will call $f after $g (f . g). Input of a $g, will be input argument of the function and return value of the $f will be output of the function: function (InputTypeOfG $inputOfG): OutputTypeOfF {...}
 */
function compose(callable $f, callable $g): Closure {
    return function ($v) use ($f, $g) {
        return $f($g($v));
    };
}

/**
 * @return mixed
 */
function requireFile(string $__filePath, bool $__once = false) {
    if ($__once) {
        return require_once $__filePath;
    }
    return require $__filePath;
}

// @TODO: Move to Byte??, merge with Converter

function formatBytes(string $bytes, string $format = null): string {
    $n = strlen($bytes);
    $s = '';
    $format = $format ?: '\x%02x';
    for ($i = 0; $i < $n; $i++) {
        $s .= sprintf($format, ord($bytes[$i]));
    }
    return $s;
}

function formatFloat($val): string {
    if (empty($val)) {
        $val = 0;
    }
    $val = str_replace(',', '.', (string) $val);
    return number_format(round(floatval($val), 2), 2, '.', ' ');
}

function hash(mixed $var): string {
    // @TODO: Use it in memoize, check all available types.
    throw new NotImplementedException();
    //return md5(json_encode($arr));
}

function equals($a, $b) {
    throw new NotImplementedException();
}

/**
 * @TODO: This method can't reliable say when a function is called with different arguments.
 */
function memoize(callable $fn): Closure {
    return function (...$args) use ($fn) {
        static $memo = [];
        /*
                $hash = \array_reduce($args, function ($acc, $var) {
                    $hash = '';
                    if (\is_object($var)) {
                        $hash .= spl_object_hash($var);
                    } elseif (\is_scalar($var)) { //  int, float, string and bool
                    return $hash;
                });
        */
        // @TODO: avoid overwritting different functions called with the same arguments.
        $hash = md5(json_encode($args)); // NB: \md5() can cause collisions
        if (array_key_exists($hash, $memo)) {
            return $memo[$hash];
        }
        return $memo[$hash] = $fn(...$args);
    };
}

/**
 * @return mixed The truthy result from the predicate
 */
function waitUntilNumOfAttempts(callable $predicate, int $waitIntervalMicroSec = null, int $numOfAttempts = 30) {
    if (null === $waitIntervalMicroSec) {
        $waitIntervalMicroSec = WAIT_INTERVAL_MICRO_SEC;
    }
    for ($i = 0; $i < $numOfAttempts; $i++) {
        $res = $predicate();
        if ($res) {
            return $res;
        }
        usleep($waitIntervalMicroSec);
    }
    throw new RuntimeException('The number of attempts has been reached');
}

/**
 * @return mixed The truthy result from the predicate
 */
function waitUntilTimeout(callable $predicate, int $timeoutMicroSec) {
    $time = microtime(true);
    while (true) {
        $res = $predicate();
        if ($res) {
            return $res;
        }
        $time += microtime(true);
        if ($time >= $timeoutMicroSec) {
            throw new RuntimeException('The timeout has been reached');
        }
        usleep($timeoutMicroSec);
    }
}

function any(callable $predicate, iterable $list): bool {
    foreach ($list as $key => $value) {
        if ($predicate($value, $key)) {
            return true;
        }
    }
    return false;
}

function apply(callable $fn, $iter): void {
    if (is_string($iter)) {
        // todo: use mb_*
        if ($iter !== '') {
            throw new NotImplementedException();
        }
    } else {
        foreach ($iter as $k => $v) {
            $fn($v, $k);
        }
    }
}

function pipe($iter, mixed $val): mixed {
    if (is_string($iter)) {
        // todo: use mb_*
        if ($iter !== '') {
            throw new NotImplementedException();
        }
    } else {
        foreach ($iter as $v) {
            $val = $v($val);
        }
    }
    return $val;
}

/**
 * Modified version from the https://github.com/nikic/iter
 * @Copyright (c) 2013 by Nikita Popov.
 *
 * Chains the iterables that were passed as arguments.
 *
 * The resulting iterator will contain the values of the first iterable, then the second, and so on.
 *
 * Example:
 *     chain(range(0, 5), range(6, 10), range(11, 15))
 *     => iterable(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15)
 */
function chain(...$iterables): iterable {
    // @TODO: Handle strings
    //_assertAllIterable($iterables);
    foreach ($iterables as $iterable) {
        foreach ($iterable as $key => $value) {
            yield $key => $value;
        }
    }
}

/**
 * @param iterable|string $haystack
 * @param mixed              $needle
 */
function contains($haystack, $needle): bool {
    if (is_string($haystack)) {
        if ($needle === '') {
            return true;
        }
        //mb_strpos() ??
        return false !== strpos($haystack, $needle);
    } elseif (is_array($haystack)) {
        return in_array($needle, $haystack, true);
    } else {
        // @TODO: iterable
        throw new NotImplementedException();
    }
}

/**
 * @param string|iterable $iter
 * @return string|Generator|array
 *     string if $list : string
 *     array if $list : array
 *     Generator otherwise
 */
function filter(callable $predicate, $iter) {
    if (is_string($iter)) {
        if ($iter !== '') {
            throw new NotImplementedException();
        }
        return '';
    }
    if (is_array($iter)) {
        $res = [];
        $intKeys = true;
        foreach ($iter as $k => $v) {
            if ($intKeys && !is_int($k)) {
                $intKeys = false;
            }
            if ($predicate($v, $k)) {
                $res[$k] = $v;
            }
        }
        return $intKeys ? array_values($res) : $res;
    } else {
        return (function () use ($predicate, $iter) {
            foreach ($iter as $k => $v) {
                if ($predicate($v, $k)) {
                    yield $k => $v;
                }
            }
        })();
    }
}

/**
 * Modified version from the https://github.com/nikic/iter
 * @Copyright (c) 2013 by Nikita Popov.
 *
 * Applies a function to each value in an iterator and flattens the result.
 *
 * The function is passed the current iterator value and should return an
 * iterator of new values. The result will be a concatenation of the iterators
 * returned by the mapping function.
 *
 * Examples
 *     flatMap(function($v) { return [-$v, $v]; }, [1, 2, 3, 4, 5]);
 *     => iterable(-1, 1, -2, 2, -3, 3, -4, 4, -5, 5)
 *
 * @param callable           $fn Mapping function: iterable function(mixed $value)
 * @param iterable|string $iter Iterable to be mapped over
 *
 * @return string|Generator|array
 */
function flatMap(callable $fn, $iter) {
    if (is_string($iter)) {
        if ($iter !== '') {
            throw new NotImplementedException();
        }
        return '';
    }
    if (is_array($iter)) {
        $newArr = [];
        foreach ($iter as $value) {
            foreach ($fn($value) as $k => $v) {
                $newArr[$k] = $v;
            }
        }
        return $newArr;
    }
    // @TODO: Handle strings
    return (function () use ($fn, $iter) {
        foreach ($iter as $value) {
            foreach ($fn($value) as $k => $v) {
                yield $k => $v;
            }
        }
    })();
}

/**
 * For abcd returns a
 */
function head($list, string $separator = null) {
    if (is_array($list)) {
        if (!count($list)) {
            throw new RuntimeException('Empty list');
        }
        return array_shift($list);
    } elseif (is_string($list)) {
        if ($list === '') {
            throw new RuntimeException('Empty list');
        }
        // @TODO, mb_substr()
        if (null === $separator) {
            return substr($list, 0, 1);
        }
        $pos = strpos($list, $separator);
        return false === $pos
            ? $list
            : substr($list, 0, $pos);
    } else {
        $empty = true;
        $head = null;
        foreach ($list as $v) {
            $empty = false;
            $head = $v;
            break;
        }
        if ($empty) {
            throw new RuntimeException('Empty list');
        }
        return $head;
    }
}

/**
 * For abcd returns abc
 */
function init($list, string $separator = null) {
    if (is_array($list)) {
        if (!count($list)) {
            throw new RuntimeException('Empty list');
        }
        return array_slice($list, 0, -1, true);
    } elseif (is_string($list)) {
        if ($list === '') {
            throw new RuntimeException('Empty list');
        }
        /*
        $parts = explode($separator, $list);
        \array_pop($parts);
        return \implode('\\', $parts);
        */
        // @TODO, mb_substr()
        $pos = strrpos($list, $separator);
        return false === $pos
            ? ''
            : substr($list, 0, $pos);
    } else {
        $empty = true;
        foreach ($list as $_) {
            $empty = false;
        }
        if ($empty) {
            throw new RuntimeException('Empty list');
        }
        throw new NotImplementedException();
    }
}

/**
 * For abcd returns d
 */
function last($list, string $separator = null) {
    if (is_array($list)) {
        if (!count($list)) {
            throw new RuntimeException('Empty list');
        }
        return array_pop($list);
    } elseif (is_string($list)) {
        if ($list === '') {
            throw new RuntimeException('Empty list');
        }
        // @TODO, mb_substr()
        if (null === $separator) {
            return substr($list, -1);
        }
        $pos = strrpos($list, $separator);
        return false === $pos
            ? $list
            : substr($list, $pos + 1);
    } else {
        $empty = true;
        $last = null;
        foreach ($list as $v) {
            $empty = false;
            $last = $v;
        }
        if ($empty) {
            throw new RuntimeException('Empty list');
        }
        return $last;
    }
}

/**
 * For abcd returns bcd
 */
function tail($list, string $separator = null) {
    if (is_array($list)) {
        if (!count($list)) {
            throw new RuntimeException('Empty list');
        }
        array_shift($list);
        return $list;
    } elseif (is_string($list)) {
        if ($list === '') {
            throw new RuntimeException('Empty list');
        }
        // @TODO, mb_substr()
        $pos = strpos($list, $separator);
        return false === $pos
            ? ''
            : substr($list, $pos + 1);
    } else {
        $empty = true;
        $gen = function () use ($list, &$empty) {
            foreach ($list as $v) {
                if ($empty) {
                    $empty = false;
                } else {
                    yield $v;
                }
            }
            if ($empty) {
                throw new RuntimeException('Empty list');
            }
        };
        return $gen();
    }
}

/**
 * @return string|Generator|array
 */
function map(callable $fn, $iter) {
    if (is_string($iter)) {
        if ($iter !== '') {
            throw new NotImplementedException();
        }
        return '';
    }
    if (is_array($iter)) {
        $newArr = [];
        foreach ($iter as $k => $v) {
            $newArr[$k] = $fn($v, $k);
        }
        return $newArr;
    }
    // @TODO: Handle strings
    return (function () use ($fn, $iter) {
        foreach ($iter as $k => $v) {
            yield $k => $fn($v, $k);
        }
    })();
}

/**
 * Modified reduce() from the https://github.com/nikic/iter
 * @Copyright (c) 2013 by Nikita Popov.
 *
 * Left fold: folds $list using a function $fn into a single value. The `reduce` function also known as the `fold`.
 *
 * Examples:
 *      lfold(op('+'), range(1, 5), 0)
 *      => 15
 *      lfold(op('*'), range(1, 5), 1)
 *      => 120
 *
 * @param callable(mixed, mixed, mixed): mixed $fn Reduction function: (mixed $acc, mixed $curValue, mixed $curKey)
 *     where $acc is the accumulator
 *           $curValue is the current element
 *           $curKey is a key of the current element
 *     The reduction function must return a new accumulator value.
 * @param iterable<mixed, mixed>|string     $list Iterable to reduce.
 * @param mixed                                $initial Start value for accumulator. Usually identity value of $function.
 *
 * @return mixed Result of the reduction.
 */
function lfold(callable $fn, iterable|string $list, mixed $initial = null): mixed {
    if (is_iterable($list)) {
        $acc = $initial;
        foreach ($list as $key => $cur) {
            $acc = $fn($acc, $cur, $key);
        }
        return $acc;
    }
    // @TODO:  array mb_split ( string $pattern , string $string [, int $limit = -1 ] )
    throw new NotImplementedException();
}

/**
 * ucfirst() working for UTF-8, https://www.php.net/manual/en/function.ucfirst.php#57298
 * @param string|Stringable $list
 * @return string
 */
function ucfirst(string|Stringable $list): string {
    $list = (string) $list;
    $fc = mb_strtoupper(mb_substr($list, 0, 1));
    return $fc . mb_substr($list, 1);
}

/**
 * Opposite to unindent()
 * @param string|Stringable|int|float $text
 * @param int $indent Number of spaces
 * @return string
 */
function indent(string|Stringable|int|float $text, int $indent = INDENT_SIZE): string {
    return preg_replace('~^~m', str_repeat(' ', $indent), (string) $text);
}

/**
 * Opposite to indent()
 * @param string|Stringable|int|float $text
 * @param int $indent Number of spaces
 * @return string
 */
function unindent(string|Stringable|int|float $text, int $indent = INDENT_SIZE): string {
    return preg_replace('~^' . str_repeat(' ', $indent) . '~m', '', (string) $text);
}

/**
 * @deprecated should be removed after PHP 8.2 and replaced with iterator_to_array()
 * Alternative to iterator_to_array(), as the iterator_to_array() does not support arrays as the first argument
 * @param iterable $it
 * @return array
 */
function toArr(iterable $it): array {
    if (is_array($it)) {
        return $it;
    }
    if ($it instanceof ArrayObject) {
        return $it->getArrayCopy();
    }
    $arr = [];
    $i = 0;
    $intKeys = true;
    foreach ($it as $key => $val) {
        if (!preg_match('~^\d+$~s', (string) $key)) {
            $intKeys = false;
            break;
        }
        $arr[$i] = $val;
        $i++;
    }
    if ($intKeys) {
        return $arr;
    }
    $arr = [];
    foreach ($it as $key => $val) {
        $arr[$key] = $val;
    }
    return $arr;
}

/**
 * Returns an array (set, order is not important) of all subsets having size 2^count($arr).
 * If $k >= 0 then will generate only k-subsets (subsets of size $k).
 *
 * of all subsets,the number of elements of the output is 2^count($arr).
 * The $arr must be either empty or non-empty and have numeric keys.
 *
 * @psalm-param array<string, mixed> $set
 * @psalm-param int $arr
 * @return array
 */
function subsets(array $set, int $k = -1): array {
    if (count($set) > (8 * PHP_INT_SIZE)) {
        throw new OutOfBoundsException(
            'Too large array/set, max number of elements of the input can be ' . (8 * PHP_INT_SIZE)
        );
    }
    if ($k > -1) {
        throw new NotImplementedException();
    }

    // Original algo is written by Brahmananda (https://www.quora.com/How-do-I-generate-all-subsets-of-a-set-in-Java-iteratively)
    $subsets = [];
    $n = count($set);
    $numOfSubsets = 1 << $n; // 2^$n
    for ($i = 0; $i < $numOfSubsets; $i++) {
        $subsetBits = $i;
        $subset = [];
        for ($j = 0; $j < $n; $j++) { // $n is the width of the bit field, number of elements in the input set.
            if ($subsetBits & 1) {  // is the right bit is 1?
                $subset[] = $set[$j];
            }
            $subsetBits = $subsetBits >> 1; // process next bit
        }
        $subsets[] = $subset;
    }
    return $subsets;
}

function isSubset(array $arrA, array $arrB): bool {
    return intersect($arrA, $arrB) == $arrB;
}

/**
 * Union for sets, for difference use \array_diff(), for intersection use \array_intersect().
 */
function union(...$arr): array {
    // @TODO: make it work for array of arrays and other cases.
    return array_unique(array_merge(...$arr));
}

function intersect(...$arr): array {
    return array_intersect_key(...$arr);
}

function cartesianProduct(array $arrA, array $arrB) {
    // @TODO: work for iterable
    $res = [];
    foreach ($arrA as $v1) {
        foreach ($arrB as $v2) {
            $res[] = [$v1, $v2];
        }
    }
    return $res;
}

/**
 * @psalm-param array<string, mixed> $set
 * @return array
 */
function permutations(array $set): array {
    // todo: https://en.wikipedia.org/wiki/Heap%27s_algorithm

    $perms = function (array $set): array {
        $n = count($set);

        $permutations = [];

        $permutations[] = $set;

        if ($n <= 1) {
            return $permutations;
        }
        if ($n == 2) {
            $permutations[] = [$set[1], $set[0]];
            return $permutations;
        }
        throw new UnexpectedValueException();
    };

    if (count($set) <= 2) {
        return $perms($set);
    }

    $permutations = [];
    $i = 0;
    $origSet = $set;
    $n = count($set);
    while (true) {
        $set = $origSet;
        $removed = array_splice($set, $i, 1);
        foreach (permutations($set) as $permutation) {
            $permutations[] = array_merge($removed, $permutation);
        }
        $i++;
        if ($i >= $n) {
            break;
        }
    }
    return $permutations;
}

/**
 * @psalm-param array<string, mixed> $arr
 */
function combinations(array $arr): array {
    throw new NotImplementedException();
}

/**
 * Modified \Zend\Stdlib\ArrayUtils::merge() from the http://github.com/zendframework/zf2
 *
 * Merge two arrays together.
 *
 * If an integer key exists in both arrays and preserveNumericKeys is false, the value
 * from the second array will be appended to the first array. If both values are arrays, they
 * are merged together, else the value of the second array overwrites the one of the first array.
 */
function merge(array $arrA, array $arrB, bool $resetIntKeys = true): array {
    foreach ($arrB as $key => $value) {
        if (isset($arrA[$key]) || array_key_exists($key, $arrA)) {
            if ($resetIntKeys && is_int($key)) {
                $arrA[] = $value;
            } elseif (is_array($value) && is_array($arrA[$key])) {
                $arrA[$key] = merge($arrA[$key], $value, $resetIntKeys);
            } else {
                $arrA[$key] = $value;
            }
        } else {
            $arrA[$key] = $value;
        }
    }
    return $arrA;
}

/**
 * Symmetrical difference of the two sets: ($a \ $b) U ($b \ $a).
 * If for $a[$k1] and $b[$k2] string keys are equal the value $b[$k2] will overwrite the value $a[$k1].
 */
function symDiff(array $arrA, array $arrB): array {
    $diffA = array_diff($arrA, $arrB);
    $diffB = array_diff($arrB, $arrA);
    return union($diffA, $diffB);
}

function unsetOne(array $arr, $val, bool $resetIntKeys = true, bool $allOccur = false, bool $strict = true): array {
    while (true) {
        $key = array_search($val, $arr, $strict);
        if (false === $key) {
            break;
        }
        unset($arr[$key]);
        if (!$allOccur) {
            break;
        }
    }
    return $resetIntKeys && all(fn ($key) => is_int($key), array_keys($arr))
        ? array_values($arr)
        : $arr;
}

function unsetMany(
    array $arr,
    iterable $val,
    bool $resetIntKeys = true,
    bool $allOccur = false,
    bool $strict = true
): array {
    // NB: unsetMany() can't merged with unsetOne() as $val in unsetOne() can be array (iterable), i.e. unsetOne() has to support unsetting arrays.
    foreach ($val as $v) {
        while (true) {
            $key = array_search($v, $arr, $strict);
            if (false === $key) {
                break;
            }
            unset($arr[$key]);
            if (!$allOccur) {
                break;
            }
        }
    }
    return $resetIntKeys && all(fn ($key) => is_int($key), array_keys($arr))
        ? array_values($arr)
        : $arr;
}

/**
 * Unsets all items of array with $key recursively.
 * @todo: remove reference
 * todo: make it work similar to unsetOne() and unsetMulti(), rename
 */
function unsetRecursive(array &$arr, $key): array {
    unset($arr[$key]);
    foreach (array_keys($arr) as $k) {
        if (is_array($arr[$k])) {
            unsetRecursive($arr[$k], $key);
        }
    }
    return $arr;
}

function flatten(array $arr): array {
    $result = [];
    foreach ($arr as $val) {
        if (is_array($val)) {
            $result = array_merge($result, flatten($val));
        } else {
            $result[] = $val;
        }
    }
    return $result;
}

function only(array $arr, array $keys, $createMissingItems = true): array {
    if ($createMissingItems) {
        $newArr = [];
        foreach ($keys as $key) {
            $newArr[$key] = isset($arr[$key]) ? $arr[$key] : null;
        }
        return $newArr;
    }
    return array_intersect_key($arr, array_flip(array_values($keys)));
}

/**
 * Compares sets not strictly. Each element of each array must be scalar. NB: It is not the same as comparing arrays as order for sets is not important.
 * @return bool
 */
function setsEqual(array $arrA, array $arrB): bool {
    return count($arrA) === count($arrB) && count(array_diff($arrA, $arrB)) === 0;
}

function index(array $matrix, $keyForIndex, bool $drop = false): array {
    $result = [];
    foreach ($matrix as $row) {
        if (!isset($row[$keyForIndex])) {
            throw new RuntimeException();
        }
        $k = $row[$keyForIndex];
        if ($drop) {
            unset($row[$keyForIndex]);
        }
        $result[$k] = $row;
    }
    return $result;
}

function camelizeKeys(array $arr): array {
    $result = [];
    foreach ($arr as $key => $value) {
        $result[camelize($key)] = $value;
    }
    return $result;
}

function underscoreKeys(array $arr): array {
    $result = [];
    foreach ($arr as $key => $value) {
        $result[underscore($key)] = $value;
    }
    return $result;
}

/**
 * @param array<string> $regexes
 * @return string
 */
function compileRe(array $regexes, string $subpatternOpts = null): string {
    return '(' . $subpatternOpts . str_replace('~', '\~', implode('|', $regexes)) . ')';
}

function isUtf8Text(string $text): bool {
    return (bool) preg_match('/.*/us', $text); // [u/PCRE_UTF8](https://www.php.net/manual/en/reference.pcre.pattern.modifiers.php)
}
