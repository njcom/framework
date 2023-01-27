<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Base;

use Morpho\Fs\Path;

use function defined;
use function error_reporting;
use function in_array;
use function ini_get;
use function ini_set;
use function php_uname;
use function strpos;
use function strtolower;
use function sys_get_temp_dir;

abstract class Env {
    public const ENCODING = 'UTF-8';
    public const TIMEZONE = 'UTC';

    public const SUCCESS_CODE = 0;
    public const FAILURE_CODE = 1;

    public static function isXdebugEnabled(): bool {
        return self::boolIniVal('xdebug.default_enable');
    }

    /**
     * Returns true if the ini setting with the $name can be interpreted as true.
     */
    public static function boolIniVal(string $name): bool {
        // @TODO: can we use just (bool) ini_get()?
        return self::iniValToBool(ini_get($name));
    }

    /**
     * Converts any value that can be used in the ini confs to the bool value.
     */
    public static function iniValToBool($value): bool {
        // Basic idea found here: php.net/ini_get.
        static $map = [
            // true values:
            'on'    => true,
            'true'  => true,
            'yes'   => true,
            '1'     => true,
            // false values:
            'off'   => false,
            'false' => false,
            'no'    => false,
            'none'  => false,
            ''      => false,
            '0'     => false,
        ];
        return $map[strtolower((string)$value)] ?? (bool)$value;
    }

    public static function is64BitCpu(): bool {
        return PHP_INT_SIZE === 8;
    }

    public static function is32BitCpu(): bool {
        return PHP_INT_SIZE === 4;
    }

    public static function isCli(): bool {
        return PHP_SAPI == 'cli';
    }

    public static function isWin(): bool {
        return defined('PHP_WINDOWS_VERSION_BUILD');//DIRECTORY_SEPARATOR == '\\';
    }

    public static function isLinux(): bool {
        // todo: improve detection
        return self::isUnix() && !self::isMac();
    }

    public static function isUnix(): bool {
        return DIRECTORY_SEPARATOR == '/';
    }

    public static function isMac(): bool {
        return false !== strpos(php_uname('s'), 'Darwin');
    }

    /**
     * Returns true if the ini-value looks like bool.
     */
    public static function isBoolIniVal(int|string|float|bool $value): bool {
        return in_array(
            strtolower((string)$value),
            ['on', 'true', 'yes', '1', 1, 'off', 'false', 'none', '', '0', 0],
            true
        );
    }

    public static function tmpDirPath(): string {
        return Path::normalize(sys_get_temp_dir());
    }

    public static function enableExpectations(): void {
        // http://php.net/assert#function.assert.expectations
        Must::beTruthy(ini_get('zend.assertions') === '1', "The 'zend.assertions' ini parameter must be set to 1 for expectations");
        ini_set('assert.active', '1');
        ini_set('assert.exception', '1');
    }

    public static function init(): void {
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', '0');
        setlocale(LC_ALL, ''); // to fix at least escapeshellarg() to awork with UTF-8.
        /* todo: as alternative try (see getconf.c from glibc):
        setlocale (LC_CTYPE, "");
        setlocale (LC_MESSAGES, "");
        */
        ini_set('date.timezone', self::TIMEZONE);
        ini_set('default_charset', self::ENCODING);
    }
}
