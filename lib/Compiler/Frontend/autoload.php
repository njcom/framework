<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

// Matches single-line and multi-line C-style comments:
// https://stackoverflow.com/a/59094308
const C_COMMENTS_RE = '\\/\\*[\\s\\S]*?\\*\\/|\\/\\/.*';
// https://stackoverflow.com/a/59094308
const C_COMMENTS_FULL_RE = '~\\/\\*[\\s\\S]*?\\*\\/|\\/\\/.*~m';
