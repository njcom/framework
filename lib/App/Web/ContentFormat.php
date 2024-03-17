<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

class ContentFormat {
    // NB: The constants are used as the enums are not supported as arrays' keys.
    public const ANY = 'any';
    public const BIN = 'bin';
    public const HTML = 'html';
    public const JSON = 'json';
    public const TEXT = 'text';
    public const XML = 'xml';
}
