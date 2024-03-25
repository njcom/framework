<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
use FastRoute\Dispatcher as IDispatcher;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Morpho\Base\IFn;
use Morpho\Base\IHasServiceManager;
use Morpho\Base\ServiceManager;
use Morpho\Caching\ICache;
use UnexpectedValueException;

use function Morpho\Base\compose;
use function Morpho\Base\only;
use function Morpho\Caching\cacheKey;

class FastRouter implements IHasServiceManager, IFn {
    protected ServiceManager $serviceManager;

    protected ICache $cache;

    protected string $cacheKey;

    public function __construct() {
        $this->cacheKey = cacheKey($this, __FUNCTION__);
    }

    public function setServiceManager(ServiceManager $serviceManager): void {
        $this->serviceManager = $serviceManager;
        $this->cache = $serviceManager['routerCache'];
    }

    public function __invoke(mixed $context): mixed {
        $routeInfo = $this->mkRouteDispatcher()
            ->dispatch($context->httpMethod->value, $context->uri->path()->toStr(false));
        $context->handler = match ($routeInfo[0]) {
            IDispatcher::NOT_FOUND => $this->conf()['handlers']['notFound'], // 404 Not Found
            IDispatcher::METHOD_NOT_ALLOWED => $this->conf()['handlers']['methodNotAllowed'], // 405 Method Not Allowed
            IDispatcher::FOUND => array_merge($routeInfo[1], ['args' => $routeInfo[2] ?? []]), // 200 OK
            default => throw new UnexpectedValueException(),
        };
        return $context;
    }

    protected function mkRouteDispatcher(): IDispatcher {
        if (!$this->cache->has($this->cacheKey)) {
            $this->rebuildRoutes();
        }
        $dispatchData = $this->cache->get($this->cacheKey);
        return new GroupCountBasedDispatcher($dispatchData);
    }

    public function rebuildRoutes(): void {
        $routeCollector = new RouteCollector(new StdRouteParser(), new GroupCountBasedDataGenerator());
        foreach ($this->routesMeta() as $routeMeta) {
            /*
            $routeMeta['uri'] = \preg_replace_callback('~\$[a-z_][a-z_0-9]*~si', function ($matches) {
                $var = \array_pop($matches);
                return '{' . \str_replace('$', '', $var) . ':[^/]+}';
            }, $routeMeta['uri']);
            */
            $routeCollector->addRoute(
                $routeMeta['httpMethod'],
                $routeMeta['uri'],
                only($routeMeta, ['module', 'class', 'method', 'modulePath', 'controllerPath'])
            );
        }
        $dispatchData = $routeCollector->getData();
        $this->cache->set($this->cacheKey, $dispatchData);
    }

    protected function routesMeta(): iterable {
        $moduleIndex = $this->serviceManager['backendModuleIndex'];
        $modules = function () use ($moduleIndex) {
            foreach ($moduleIndex as $moduleName) {
                yield $moduleIndex->module($moduleName);
            }
        };
        return compose($this->serviceManager['routeMetaProvider'], new ActionMetaProvider())($modules);
    }

    protected function conf(): array {
        return $this->serviceManager->conf['router'];
    }
}
