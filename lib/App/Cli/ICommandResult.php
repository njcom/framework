<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Cli;

use IteratorAggregate;
use Stringable;
use Traversable;

interface ICommandResult extends IteratorAggregate, Stringable {
    public function command(): string;

    public function stdOut(): string;

    public function stdErr(): string;

    public function exitCode(): int;

    public function isError(): bool;

    public function lines(): Traversable;

    public function __toString(): string;
}
