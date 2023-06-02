<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Systemd;

/**
 * See https://www.freedesktop.org/software/systemd/man/daemon.html
 */
class ActivationType {
    public const BOOT = 'boot';
    public const SOCKET = 'socket';
    public const DBUS = 'dbus';
    public const DEVICE = 'device';
    public const PATH = 'path';
    public const TIMER = 'timer';
}
