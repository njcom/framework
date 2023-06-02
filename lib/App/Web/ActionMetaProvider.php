<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Morpho\App\ActionMetaProvider as BaseActionMetaProvider;
use ReflectionClass;

class ActionMetaProvider extends BaseActionMetaProvider {
    public function controllerFilter(): callable {
        if (null === $this->controllerFilter) {
            $controllerFilter = parent::controllerFilter();
            $this->controllerFilter = function (ReflectionClass $rClass) use ($controllerFilter): bool {
                if (!$controllerFilter($rClass)) {
                    return false;
                }
                $docComments = $rClass->getDocComment();
                if (!$docComments) {
                    return true;
                }
                return !preg_match('~\s*@noRoutes\s*~si', $docComments);
            };
        }
        return $this->controllerFilter;
    }

    protected function baseControllerClasses(): array {
        return [Controller::class];
    }
}