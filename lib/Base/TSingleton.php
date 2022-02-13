<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Base;

trait TSingleton {
    private static $instance;

    public static function instance(): static {
        if (!self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public static function resetState(): void {
        self::$instance = null;
    }
}
