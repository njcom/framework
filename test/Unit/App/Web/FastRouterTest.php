<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use ArrayObject;
use FastRoute\Dispatcher as IDispatcher;
use Morpho\App\Web\FastRouter;
use Morpho\App\Web\HttpMethod;
use Morpho\App\Web\IRequest;
use Morpho\App\Web\IResponse;
use Morpho\Uri\Path;
use Morpho\Uri\Uri;
use Morpho\Base\IServiceManager;
use Morpho\Base\NotImplementedException;
use Morpho\Caching\ICache;
use Morpho\Testing\TestCase;

class FastRouterTest extends TestCase {
    public static function dataRoute(): iterable {
        // valid HTTP method and path
        yield [
            HttpMethod::Get,
            '/foo/bar',
            [
                IDispatcher::FOUND,
                ['this is found handler'],
                ['my args'],
            ],
            [
                'this is found handler',
                'args' => ['my args'],
            ],
        ];
        // valid HTTP method, invalid path
        yield [
            HttpMethod::Get,
            '/foo',
            [
                IDispatcher::NOT_FOUND,
                null,
            ],
            [
                'this is notFound handler',
            ],
        ];
        // invalid HTTP method, valid path
        yield [
            HttpMethod::Patch,
            '/foo/bar',
            [
                IDispatcher::METHOD_NOT_ALLOWED,
                null,
            ],
            [
                'this is methodNotAllowed handler',
            ],
        ];
    }

    /**
     * @dataProvider dataRoute
     */
    public function testRoute(HttpMethod $httpMethod, string $requestPath, array $routeInfo, array $expectedHandler) {
        $dispatcher = $this->createMock(IDispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->identicalTo($httpMethod->value), $this->identicalTo($requestPath))
            ->willReturn($routeInfo);

        $uri = $this->createMock(Uri::class);
        $uri->expects($this->any())
            ->method('path')
            ->willReturn(new Path($requestPath));

        $request = $this->mkRequest($uri, $httpMethod);

        $router = $this->createPartialMock(
            FastRouter::class,
            [
                'mkDispatcher',
            ]
        );
        $router->expects($this->once())
            ->method('mkDispatcher')
            ->willReturn($dispatcher);

        $serviceManager = $this->createMock(IServiceManager::class);
        $serviceManager->expects($this->any())
            ->method('offsetGet')
            ->with($this->identicalTo('routerCache'))
            ->willReturn($this->createMock(ICache::class));
        $serviceManager->expects($this->any())
            ->method('conf')
            ->willReturn(
                [
                    'router' => [
                        'handlers' => [
                            'badRequest'       => ['this is badRequest handler'],
                            'notFound'         => ['this is notFound handler'],
                            'methodNotAllowed' => ['this is methodNotAllowed handler'],
                        ],
                    ],
                ]
            );
        $router->setServiceManager($serviceManager);

        $router->route($request);

        $this->assertSame($expectedHandler, $request->handler());
    }

    protected function mkRequest(Uri $uri, HttpMethod $httpMethod): IRequest {
        return new class ($uri, $httpMethod) implements IRequest {
            private array $handler;

            public function __construct(private Uri $uri, private HttpMethod $method) {
            }

            public function getIterator(): \Traversable {
                throw new NotImplementedException();
            }

            public function offsetExists(mixed $offset): bool {
                throw new NotImplementedException();
            }

            public function offsetGet(mixed $offset): mixed {
                throw new NotImplementedException();
            }

            public function offsetSet(mixed $offset, mixed $value): void {
                throw new NotImplementedException();
            }

            public function offsetUnset(mixed $offset): void {
                throw new NotImplementedException();
            }

            public function count(): int {
                throw new NotImplementedException();
            }

            public function exchangeArray(object|array $arr) {
                throw new NotImplementedException();
            }

            public function handler(): array {
                return $this->handler;
            }

            public function setServerVars(array $vars): void {
                throw new NotImplementedException();
            }

            public function setHandler(array $handler): void {
                $this->handler = $handler;
            }

            public function setServerVar(string $name, mixed $val): void {
                throw new NotImplementedException();
            }

            public function serverVar(string $name, $default = null): mixed {
                throw new NotImplementedException();
            }

            public function setResponse(IResponse $response): void {
                throw new NotImplementedException();
            }

            public function isGetMethod(): bool {
                throw new NotImplementedException();
            }

            public function isPatchMethod(): bool {
                throw new NotImplementedException();
            }

            public function isPutMethod(): bool {
                throw new NotImplementedException();
            }

            public function isHeadMethod(): bool {
                throw new NotImplementedException();
            }

            public function post($name = null, callable|bool $filter = true): mixed {
                throw new NotImplementedException();
            }

            public function hasPost(string $name): bool {
                throw new NotImplementedException();
            }

            public function patch($name = null, callable|bool $filter = true): mixed {
                throw new NotImplementedException();
            }

            public function data(array $source, $name = null, callable|bool $filter = true): mixed {
                throw new NotImplementedException();
            }

            public function isAjax(bool $flag = null): bool {
                throw new NotImplementedException();
            }

            public function isHandled(bool $flag = null): bool {
                throw new NotImplementedException();
            }

            public function response(): IResponse {
                throw new NotImplementedException();
            }

            public function setMethod(HttpMethod $method): void {
                throw new NotImplementedException();
            }

            public function method(): HttpMethod {
                return $this->method;
            }

            public function isPostMethod(): bool {
                throw new NotImplementedException();
            }

            public function isDeleteMethod(): bool {
                throw new NotImplementedException();
            }

            public function knownMethods(): array {
                throw new NotImplementedException();
            }

            public function isKnownMethod($method): bool {
                throw new NotImplementedException();
            }

            public function args(array|string|null $names = null, callable|bool $filter = true): mixed {
                throw new NotImplementedException();
            }

            public function query($name = null, callable|bool $filter = true): mixed {
                throw new NotImplementedException();
            }

            public function hasQuery(string $name): bool {
                throw new NotImplementedException();
            }

            public function headers(): ArrayObject {
                throw new NotImplementedException();
            }

            public function setUri(Uri $uri): void {
                throw new NotImplementedException();
            }

            public function prependWithBasePath(string $uri): Uri {
                throw new NotImplementedException();
            }

            public function uri(): Uri {
                return $this->uri;
            }

            public function setTrustedProxyIps(array $ips): void {
                throw new NotImplementedException();
            }

            public function trustedProxyIps(): ?array {
                throw new NotImplementedException();
            }
        };
    }
}
