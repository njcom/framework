<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Testing;

use Countable;

interface IVfsEntry extends Countable {
    public function close(): void;

    public function name(): string;

    public function uri(): string;

    public function setUri(string $uri): void;

    public function stat(): VfsEntryStat;

    public function chmod(int $newMode): void;
}
