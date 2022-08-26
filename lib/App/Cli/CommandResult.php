<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Cli;

use Traversable;

use function Morpho\Base\lines;

abstract class CommandResult implements ICommandResult {
    protected int $exitCode;

    public function __construct(int $exitCode) {
        $this->exitCode = $exitCode;
    }

    public function isError(): bool {
        return $this->exitCode() !== Env::SUCCESS_CODE;
    }

    public function exitCode(): int {
        return $this->exitCode;
    }

    public function getIterator(): Traversable {
        return $this->lines();
    }

    public function lines(bool $filterEmpty = true, bool $trim = true): Traversable {
        return lines($this->stdOut(), $filterEmpty, $trim);
    }

    public function __toString(): string {
        return $this->stdOut();
    }
}
