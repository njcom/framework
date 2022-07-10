<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use function array_diff;
use function array_values;
use function Morpho\Base\requireFile;
use function array_filter;
use function array_merge;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function substr;

class DiffStrategy implements IDiscoverStrategy {
    /**
     * @todo: fix breakage of the order, use some heuristic like regular expressions to find out the actual order and then fix the result
     */
    public function classTypesDefinedInFile(string $filePath): array {
        $pre = self::definedClassTypes();
        requireFile($filePath);
        $post = self::definedClassTypes();
        return array_values(array_diff($post, $pre));
    }

    public static function definedClassTypes(): array {
        return array_merge(
            self::definedClasses(),
            get_declared_interfaces(),
            get_declared_traits()
        );
    }

    public static function definedClasses(): array {
        return array_filter(
            get_declared_classes(),
            function ($class) {
                // Skip anonymous classes.
                return 'class@anonymous' !== substr($class, 0, 15);
            }
        );
    }
}
