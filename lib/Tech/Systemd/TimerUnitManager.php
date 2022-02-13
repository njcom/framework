<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Systemd;

class TimerUnitManager extends UnitManager {
    public function __construct(string $unitName, string $unitFilePath) {
        parent::__construct(UnitType::TIMER, $unitName, $unitFilePath);
    }

    public function disable(bool $stop, bool $canFail): static {
        // Clean due the `Persistent=true`
        $this->sh(
            'systemctl clean --what=state ' . escapeshellarg($this->unitName . '.' . $this->unitType),
            ['check' => !$canFail]
        );
        parent::disable($stop, $canFail);
        return $this;
    }
}