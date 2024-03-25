<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web;

use Morpho\App\Web\RouteMetaProvider;
use Morpho\Testing\TestCase;

use PHPUnit\Framework\Attributes\DataProvider;

use function iterator_to_array;
use function Morpho\Base\dasherize;

class RouteMetaProviderTest extends TestCase {
    private RouteMetaProvider $routeMetaProvider;

    protected function setUp(): void {
        parent::setUp();
        $this->routeMetaProvider = new RouteMetaProvider();
    }

    public function testInterface() {
        $this->assertIsCallable(new RouteMetaProvider());
    }

    public static function dataInvoke_RestActions() {
        return [
            [
                'index',
                'GET',
                null,
            ],
            [
                'list',
                'GET',
                'list',
            ],
            [
                'new',
                'GET',
                'new',
            ],
            [
                'create',
                'POST',
                null,
            ],
            [
                'show',
                'GET',
                '{id}',
            ],
            [
                'edit',
                'GET',
                '{id}/edit',
            ],
            [
                'update',
                'PATCH',
                '{id}',
            ],
            [
                'delete',
                'DELETE',
                '{id}',
            ],
        ];
    }

    #[DataProvider('dataInvoke_RestActions')]
    public function testInvoke_RestActions(string $action, string $expectedHttpMethod, ?string $expectedActionPath) {
        $actionMetas = [
            [
                'module' => 'capture/group',
                'method' => $action,
                'class'  => 'Foo\\Bar\\Web\\My\\Nested\\VerySimpleController',
            ],
        ];
        /** @noinspection PhpParamsInspection */
        $actual = iterator_to_array($this->routeMetaProvider->__invoke($actionMetas));

        $expectedControllerPath = 'my/nested/very-simple';
        $expectedUri = '/' . explode(
                                 '/',
                                 $actionMetas[0]['module']
                             )[1] . '/' . $expectedControllerPath . (null === $expectedActionPath ? '' : '/' . $expectedActionPath);
        $this->assertEquals(
            [
                [
                    'module'         => $actionMetas[0]['module'],
                    'method'         => $actionMetas[0]['method'],
                    'class'          => $actionMetas[0]['class'],
                    'httpMethod'     => $expectedHttpMethod,
                    'uri'            => $expectedUri,
                    'modulePath'     => explode('/', $actionMetas[0]['module'])[1],
                    'controllerPath' => $expectedControllerPath,
                    'actionPath'     => $expectedActionPath,
                ],
            ],
            $actual
        );
    }

    public function testInvoke_DocCommentsWithMultipleHttpMethodsWithCustomPath() {
        $module = 'my-vendor/foo-mod';
        $method = 'doIt';
        $relUriPath = '/some/custom/{id}/edit';
        $actionMetas = [
            [
                'module'     => $module,
                'method'     => $method,
                'docComment' => "/** @POST|PATCH $relUriPath */",
                'class'      => 'Foo\\Bar\\Web\\MySimpleController',
            ],
        ];
        /** @noinspection PhpParamsInspection */
        $actual = iterator_to_array($this->routeMetaProvider->__invoke($actionMetas));
        $this->assertEquals(
            [
                [
                    'module'         => $module,
                    'class'          => $actionMetas[0]['class'],
                    'method'         => $method,
                    'docComment'     => $actionMetas[0]['docComment'],
                    'httpMethod'     => 'POST',
                    'uri'            => $relUriPath,
                    'modulePath'     => 'foo-mod',
                    'controllerPath' => 'my-simple',
                    'actionPath'     => dasherize($method),
                ],
                [
                    'module'         => $module,
                    'class'          => $actionMetas[0]['class'],
                    'method'         => $method,
                    'docComment'     => $actionMetas[0]['docComment'],
                    'httpMethod'     => 'PATCH',
                    'uri'            => $relUriPath,
                    'modulePath'     => 'foo-mod',
                    'controllerPath' => 'my-simple',
                    'actionPath'     => dasherize($method),
                ],
            ],
            $actual
        );
    }

    public static function dataDocComments() {
        yield [
            '/**
              * @GET|POST
              */',
            [
                [
                    'httpMethods' => ['GET', 'POST'],
                ],
            ],
        ];

        yield [
            '/**
             * @GET /
             */',
            [
                [
                    'httpMethods' => ['GET'],
                    'uri'         => '/',
                ],
            ],
        ];

        yield [
            '/**
             * @GET /some/path
             */',
            [
                [
                    'httpMethods' => ['GET'],
                    'uri'         => '/some/path',
                ],
            ],
        ];

        yield [
            '/**
              *
              */',
            [
            ],
        ];

        yield [
            '/**
              * @GET /foo/bar
              * @POST|PATCH /baz
              */',
            [
                [
                    'httpMethods' => ['GET'],
                    'uri'         => '/foo/bar',
                ],
                [
                    'httpMethods' => ['POST', 'PATCH'],
                    'uri'         => '/baz',
                ],
            ],
        ];
    }

    #[DataProvider('dataDocComments')]
    public function testDocComments(string $docComment, array $expected) {
        $this->assertSame($expected, RouteMetaProvider::parseDocComment($docComment));
    }
}
