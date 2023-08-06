<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

interface IRequest extends IMessage {
    public function isHandled(bool $flag = null): bool;

    public function setHandler(array $handler): void;

    public function handler(): array;

    public function response(): IResponse;

    #public function args(mixed $filter = null): array;
}