<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Systemd;

// `man systemctl`
enum SystemdState: string {
    // Early bootup, before basic.target is reached or the maintenance state entered. Exit code: > 0
    case Initializing = 'initializing';
    // Late bootup, before the job queue becomes idle for the first time, or one of the rescue targets are reached. Exit code: > 0
    case Starting = 'starting';
    // The system is fully operational. Exit code: 0
    case Running = 'running';
    // The system is operational but one or more units failed. Exit code: > 0
    case Degraded = 'degraded';
    // The rescue or emergency target is active. Exit code: > 0
    case Maintenance = 'maintenance';
    // The manager is shutting down. Exit code: > 0
    case Stopping = 'stopping';
    // The manager is not running. Specifically, this is the operational state if an incompatible program is running as system manager (PID 1). Exit code: > 0
    case Offline = 'offline';
    // The operational state could not be determined, due to lack of resources or another error cause. Exit code: > 0
    case Unknown = 'unknown';

    public static function isRunning(SystemdState $val): bool {
        return $val === SystemdState::Running;
    }
}