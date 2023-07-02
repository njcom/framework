<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\App\Web;

use Morpho\Fs\Path;
use RuntimeException;
use UnexpectedValueException;

use function explode;
use function implode;
use function Morpho\Base\{dasherize, last};
use function preg_match;
use function preg_match_all;
use function str_replace;
use function strlen;
use function strpos;
use function substr;

use const Morpho\App\CONTROLLER_SUFFIX;
use const PREG_SET_ORDER;

class RouteMetaProvider {
    private const CONTROLLER_CLASS_RE = '~(?P<controllerNs>.*?\\\\Web)\\\\(?P<controller>.*?)$~s';

    protected array $restActions = [
        'index'  => ['GET', null],        // GET    $entityType
        'list'   => ['GET', 'list'],      // GET    $entityType/list
        'new'    => ['GET', 'new'],       // GET    $entityType/new
        'create' => ['POST', null],       // POST   $entityType
        'show'   => ['GET', '{id}'],      // GET    $entityType/$entityId
        'edit'   => ['GET', '{id}/edit'], // GET    $entityType/$entityId/edit
        'update' => ['PATCH', '{id}'],    // PATCH  $entityType/$entityId
        'delete' => ['DELETE', '{id}'],   // DELETE $entityType/$entityId
    ];

    public function __invoke($actionMetas): iterable {
        foreach ($actionMetas as $actionMeta) {
            // 1 $actionMeta can be mapped to the >= 1 $routeMeta
            yield from $this->actionMetaToRoutesMeta($actionMeta);
        }
    }

    protected function actionMetaToRoutesMeta(array $actionMeta): array {
        $routeMeta = array_merge($actionMeta, $this->routeMeta($actionMeta));
        if (empty($actionMeta['docComment'])) {
            return [$routeMeta];
        }
        $parsedDocComment = self::parseDocComment($actionMeta['docComment']);
        $routesMeta = [];
        foreach ($parsedDocComment as $routeMeta1) {
            $httpMethods = $routeMeta1['httpMethods'];
            unset($routeMeta1['httpMethods']);
            foreach ($httpMethods as $httpMethod) {
                $routesMeta[] = array_merge($routeMeta, array_merge($routeMeta1, ['httpMethod' => $httpMethod]));
            }
        }
        return $routesMeta;
    }

    protected function routeMeta(array $actionMeta): array {
        $modulePath = dasherize(last($actionMeta['module'], '/'), '.');

        $basePath = '/';

        if (!preg_match(self::CONTROLLER_CLASS_RE, $actionMeta['class'], $match) || !str_ends_with(
                $match['controller'],
                CONTROLLER_SUFFIX
            )) {
            throw new UnexpectedValueException(print_r($actionMeta, true));
        }
        $controller = substr($match['controller'], 0, -strlen(CONTROLLER_SUFFIX));
        $controllerPath = str_replace('\\', '/', dasherize($controller, '\\'));

        $method = $actionMeta['method'];
        if (isset($this->restActions[$method])) {
            $actionPath = $this->restActions[$method][1];
            $httpMethod = $this->restActions[$method][0];
        } else {
            $actionPath = dasherize($method);
            $httpMethod = 'GET';
        }

        $uri = Path::combine($basePath, $modulePath, $controllerPath, $actionPath);

        return [
            'httpMethod'     => $httpMethod,
            'uri'            => $uri,
            'modulePath'     => $modulePath,
            'controllerPath' => $controllerPath,
            'actionPath'     => $actionPath,
        ];
    }

    public static function parseDocComment(string $docComment): array {
        $parsed = [];
        if (str_contains($docComment, '@')) {
            $httpMethodsRegexpPart = '(?:' . implode('|', array_column(HttpMethod::cases(), 'value')) . ')';
            $routeRegExp = '~'
                . '@(?<httpMethod>' . $httpMethodsRegexpPart . '(?:\|' . $httpMethodsRegexpPart . ')?)    # method (required)
                (?:\s+(?<uri>[^*\s]+))?                                                                   # uri    (optional)
                ~xm';
            if (preg_match_all($routeRegExp, $docComment, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $meta = [
                        'httpMethods' => explode('|', $match['httpMethod']),
                    ];
                    $uri = null;
                    if (!empty($match['uri'])) {
                        $uri = $match['uri'];
                        if ($uri[0] !== '/') {
                            throw new RuntimeException("Invalid annotations, URI must start with slash (/)");
                        }
                    }
                    if (null !== $uri) {
                        $meta['uri'] = $uri;
                    }
                    $parsed[] = $meta;
                }
            }
        }
        return $parsed;
    }
}
