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
use Morpho\Base\IHasServiceManager;
use Morpho\Base\ServiceManager;
use Morpho\Caching\ICache;
use UnexpectedValueException;

use function Morpho\Base\compose;
use function Morpho\Base\only;
use function Morpho\Caching\cacheKey;

class FastRouter implements IHasServiceManager {
    protected ServiceManager $serviceManager;

    protected ICache $cache;

    protected string $cacheKey;

    public function __construct() {
        $this->cacheKey = cacheKey($this, __FUNCTION__);
    }

    public function setServiceManager(ServiceManager $serviceManager): static {
        $this->serviceManager = $serviceManager;
        $this->cache = $serviceManager['routerCache'];
        return $this;
    }

    public function __invoke(IRequest $request): array {
        $routeInfo = $this->mkRouteDispatcher()
            ->dispatch($request->method()->value, $request->uri()->path()->toStr(false));
        switch ($routeInfo[0]) {
            case IDispatcher::NOT_FOUND: // 404 Not Found
                return $this->conf()['handlers']['notFound'];
            case IDispatcher::METHOD_NOT_ALLOWED: // 405 Method Not Allowed
                return $this->conf()['handlers']['methodNotAllowed'];
            case IDispatcher::FOUND: // 200 OK
                $handlerMeta = $routeInfo[1];
                return array_merge($handlerMeta, ['args' => $routeInfo[2] ?? []]);
            default:
                throw new UnexpectedValueException();
        }
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
