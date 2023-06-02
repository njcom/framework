<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Systemd;

class Systemd {
    public static function isSystemdBooted(): bool {
        // https://www.freedesktop.org/software/systemd/man/sd_booted.html
        return is_dir('/run/systemd/system/');
    }
}