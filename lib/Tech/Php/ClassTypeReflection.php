<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use ReflectionClass as BaseClassReflection;

use function array_reverse;

class ClassTypeReflection extends BaseClassReflection {
    public function parentClasses(bool $appendSelf = true): array {
        $rClasses = [];
        $rClass = $this;
        while ($rClass = $rClass->getParentClass()) {
            $rClasses[] = $rClass;
        }
        $rClasses = array_reverse($rClasses);
        if ($appendSelf) {
            $rClasses[] = $this;
        }
        return $rClasses;
    }
}
