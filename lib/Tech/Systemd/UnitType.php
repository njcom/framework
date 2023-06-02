<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Systemd;

class UnitType {
    public const AUTOMOUNT = 'automount';
    public const DEVICE = 'device';
    public const MOUNT = 'mount';
    public const PATH = 'path';
    public const SCOPE = 'scope';
    public const SERVICE = 'service';
    public const SLICE = 'slice';
    public const SOCKET = 'socket';
    public const SWAP = 'swap';
    public const TARGET = 'target';
    public const TIMER = 'timer';
}
