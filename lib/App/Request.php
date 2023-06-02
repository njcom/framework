<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

abstract class Request extends Message implements IRequest {
    private array $handler = [];

    private bool $isHandled = false;

    public function isHandled(bool $flag = null): bool {
        if ($flag !== null) {
            $this->isHandled = $flag;
        }
        return $this->isHandled;
    }

    public function setHandler(array $handler): void {
        $this->handler = $handler;
    }

    public function handler(): array {
        return $this->handler;
    }
}
