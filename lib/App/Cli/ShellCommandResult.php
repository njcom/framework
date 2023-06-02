<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Cli;

class ShellCommandResult extends CommandResult {
    protected string $stdOut;
    protected string $stdErr;
    protected string $command;

    public function __construct(string $command, int $exitCode, string $stdOut, string $stdErr) {
        parent::__construct($exitCode);
        $this->command = $command;
        $this->stdOut = $stdOut;
        $this->stdErr = $stdErr;
    }

    public function command(): string {
        return $this->command;
    }

    public function stdOut(): string {
        return $this->stdOut;
    }

    public function stdErr(): string {
        return $this->stdErr;
    }

    public function count(): int {
        return iterator_count($this->lines());
    }
}
