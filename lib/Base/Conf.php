<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Base;

use ArrayObject;

use function array_diff_key;
use function array_flip;
use function array_keys;
use function array_merge;
use function count;

class Conf extends ArrayObject {
    protected ?array $default = null;

    public function __construct($values = null) {
        if (null === $values) {
            if (null !== $this->default) {
                parent::__construct($this->default);
            } else {
                parent::__construct();
            }
        } else {
            parent::__construct($values);
        }
    }

    /**
     * Merges $defaultConf and $conf, throws InvalidConfException if $conf contains any keys not present in $defaultConf
     */
    public static function check(array $defaultConf, ?array $conf): array {
        if (null === $conf || count($conf) === 0) {
            return $defaultConf;
        }
        $diff = array_diff_key($conf, array_flip(array_keys($defaultConf)));
        if (count($diff)) {
            throw new InvalidConfException($diff);
        }
        return array_merge($defaultConf, $conf);
    }

    public function merge(Conf|ArrayObject|array $conf, bool $recursive = true): static {
        if ($conf instanceof ArrayObject) {
            $conf = $conf->getArrayCopy();
        }
        if ($recursive) {
            $this->exchangeArray(merge($this->getArrayCopy(), $conf));
        } else {
            $this->exchangeArray(array_merge($this->getArrayCopy(), $conf));
        }
        return $this;
    }
}
