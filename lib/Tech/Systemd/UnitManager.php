<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Systemd;

use Morpho\App\Cli\ICommandResult;
use UnexpectedValueException;

use function Morpho\App\Cli\sh;

class UnitManager {
    protected string $unitType;
    protected string $unitName;
    private string $unitFilePath;

    public function __construct(string $unitType, string $unitName, string $unitFilePath) {
        if (!in_array($unitType, Meta::knownUnitTypes(), true)) {
            throw new UnexpectedValueException();
        }
        $this->unitType = $unitType;
        $this->unitName = $unitName;
        if (!str_ends_with($unitFilePath, '.' . $unitType)) {
            throw new UnexpectedValueException("The unit file must end with the '." . $unitType . "' extension");
        }
        $this->unitFilePath = $unitFilePath;
    }

    public function enable(bool $start, bool $canFail) {
        $this->sh(
            'systemctl enable' . ($start ? ' --now' : '') . ' ' . escapeshellarg($this->unitFilePath),
            ['check' => !$canFail]
        );
    }

    protected function sh(string $cmd, array $conf = null): ICommandResult {
        return sh($cmd, $conf);
    }

    public function disable(bool $stop, bool $canFail): static {
        $this->sh(
            'systemctl disable' . ($stop ? ' --now' : '') . ' ' . escapeshellarg(
                $this->unitName . '.' . $this->unitType
            ),
            ['check' => !$canFail]
        );
        return $this;
    }

    public function stop(bool $canFail): static {
        $this->sh('systemctl stop ' . escapeshellarg($this->unitName . '.' . $this->unitType), ['check' => !$canFail]);
        return $this;
    }
}