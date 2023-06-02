<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Cli;

use IteratorAggregate;
use Stringable;
use Countable;

interface ICommandResult extends IteratorAggregate, Stringable, Countable {
    public function command(): string;

    public function stdOut(): string;

    public function stdErr(): string;

    public function exitCode(): int;

    public function isError(): bool;

    /**
     * @param bool $asArr
     * @return iterable: Traversable if $asArr == false, array otherwise.
     */
    public function lines(bool $asArr = true): iterable;

    public function __toString(): string;
}
