<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use Morpho\Base\IFn;

abstract class Plugin implements IFn {
    public function __invoke(mixed $value = null): mixed {
        return $this;
    }
}
