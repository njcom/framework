<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use ArrayObject;
use Throwable;

interface IExceptionHandler {
    public function register(): void;

    public function unregister(): void;

    public function listeners(): ArrayObject;

    public function handleException(Throwable $e): void;
}
