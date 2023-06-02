<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

class Re {
    public const WHITESPACES = '\\s+';
    //public const WHITESPACES_FULL = '~\\s+~s';
    public const NOT_WHITESPACES = '\\S+';

    public const SINGLE_QUOTED = "'((?:[^'\\\\]|\\\\.)*)'";

    public const DOUBLE_QUOTED = '"((?:[^"\\\\]|\\\\.)*)"';

    // Matches single-line and multi-line C-style comments (https://stackoverflow.com/a/59094308):
    const C_COMMENT = '\/\*[\s\S]*?\*\/|\/\/.*';
    //const C_COMMENT_FULL = '~' . self::C_COMMENT . '~m';

    const PHP_SHEBANG = '#!/usr/bin/env\\s+php';
    //const PHP_SHEBANG_FULL = '~^' . self::PHP_SHEBANG . '$~';
}