<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App;

use Closure;
use Morpho\Base\IFn;
use Morpho\Tech\Php\FileReflection;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function array_values;
use function in_array;

abstract class ActionMetaProvider implements IFn {
    protected $controllerFilter;
    protected $actionFilter;

    public function __construct(callable $controllerFilter = null, callable $actionFilter = null) {
        $this->controllerFilter = $controllerFilter;
        $this->actionFilter = $actionFilter;
    }

    /**
     * @param iterable|Closure $modules Iterable over BackendModule or \Closure returning \Generator
     * @return iterable Iterable over action meta
     * @throws ReflectionException
     */
    public function __invoke(mixed $modules): iterable {
        $controllerFilter = $this->controllerFilter();
        if ($modules instanceof Closure) {
            $modules = $modules();
        }
        foreach ($modules as $module) {
            /** @var BackendModule $module */
            foreach ($module->controllerFilePaths(true) as $controllerFilePath) {
                foreach ((new FileReflection($controllerFilePath))->classes() as $rClass) {
                    if (!$controllerFilter($rClass)) {
                        continue;
                    }
                    yield from $this->actionMetaFromController($module, $rClass);
                }
            }
        }
    }

    public function controllerFilter(): callable {
        if (null === $this->controllerFilter) {
            $this->controllerFilter = function (ReflectionClass $rClass): bool {
                if ($rClass->isAbstract()) {
                    return false;
                }
                return str_ends_with($rClass->getName(), CONTROLLER_SUFFIX);
            };
        }
        return $this->controllerFilter;
    }

    public function actionMetaFromController($module, ReflectionClass $rClass): iterable {
        $actionFilter = $this->actionFilter();
        $controllerMeta = [
            'module'   => $module->name(),
            'filePath' => $rClass->getFileName(),
            'class'    => $rClass->getName(),
        ];
        require_once $controllerMeta['filePath'];
        $actionsMeta = [];
        foreach ((new ReflectionClass($controllerMeta['class']))->getMethods(ReflectionMethod::IS_PUBLIC) as $rMethod) {
            if (!$actionFilter($rMethod)) {
                continue;
            }
            $method = $rMethod->getName();
            $actionsMeta[$method] = [
                'module'   => $controllerMeta['module'],
                'class'    => $controllerMeta['class'],
                'filePath' => $controllerMeta['filePath'],
                'method'   => $method,
            ];
            $docComment = $rMethod->getDocComment();
            if ($docComment) {
                $actionsMeta[$method]['docComment'] = $docComment;
            }
        }
        yield from array_values($actionsMeta);
    }

    /**
     * @throws \ReflectionException
     */
    public function actionFilter(): callable {
        if (null === $this->actionFilter) {
            $baseControllerClasses = $this->baseControllerClasses();
            $ignoredMethods = [];
            foreach ($baseControllerClasses as $baseControllerClass) {
                foreach (
                    (new ReflectionClass($baseControllerClass))->getMethods(
                        ReflectionMethod::IS_PUBLIC
                    ) as $rMethod
                ) {
                    $ignoredMethods[] = $rMethod->getName();
                }
            }
            $this->actionFilter = function (ReflectionMethod $rMethod) use ($ignoredMethods): bool {
                $method = $rMethod->getName();
                if (in_array($method, $ignoredMethods)) {
                    return false;
                }
                if (!preg_match('~^[a-z]~si', $method)) {
                    return false;
                }
                $docComment = $rMethod->getDocComment();
                if ($docComment) {
                    if (str_contains($docComment, '@notAction')) {
                        return false;
                    }
                }
                return true;
            };
        }
        return $this->actionFilter;
    }

    abstract protected function baseControllerClasses(): array;

    public function setControllerFilter(callable $controllerFilter): static {
        $this->controllerFilter = $controllerFilter;
        return $this;
    }

    public function setActionFilter(callable $actionFilter): static {
        $this->actionFilter = $actionFilter;
        return $this;
    }
}
