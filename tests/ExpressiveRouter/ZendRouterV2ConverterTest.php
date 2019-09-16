<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouterTest\ExpressiveRouter;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ConverterInterface;
use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\InvalidRouteConfigurationException;
use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ZendRouterV2Converter;
use Boesing\ZendRouterToExpressiveRouter\Middleware\DummyMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Zend\Expressive\Router\Route;
use Zend\Router\Http\Chain;
use Zend\Router\Http\Hostname;
use Zend\Router\Http\Literal;
use Zend\Router\Http\Method;
use Zend\Router\Http\Part;
use Zend\Router\Http\Placeholder;
use Zend\Router\Http\Regex;
use Zend\Router\Http\Scheme;
use Zend\Router\Http\Segment;
use Zend\Router\Http\Wildcard;
use Zend\Router\RouteInvokableFactory;
use Zend\Router\RoutePluginManager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

use function array_map;
use function count;

final class ZendRouterV2ConverterTest extends TestCase
{
    /**
     * @test
     */
    public function willDetectRouteWithoutChildRoutesAndMayTerminateFalse() : void
    {
        $this->expectException(InvalidRouteConfigurationException::class);

        $routes = [
            'foo' => [
                'type'          => 'literal',
                'may_terminate' => false,
            ],
        ];

        $converter = new ZendRouterV2Converter(
            $this->configurationWithoutBlacklistMock()->reveal(),
            $this->routePluginManager()
        );

        $converter->convert($routes);
    }

    /**
     * @return ZendRouterV2Converter\ConfigurationInterface|ObjectProphecy
     */
    private function configurationWithoutBlacklistMock() : ObjectProphecy
    {
        $configuration = $this->prophesize(ZendRouterV2Converter\ConfigurationInterface::class);
        $configuration->getBlacklistedRouteNames()->willReturn([]);

        return $configuration;
    }

    private function routePluginManager() : RoutePluginManager
    {
        $manager = new RoutePluginManager(new ServiceManager());
        (new Config([
            'aliases'   => [
                'hostname' => Hostname::class,
                'literal'  => Literal::class,
                'method'   => Method::class,
                'part'     => Part::class,
                'regex'    => Regex::class,
                'scheme'   => Scheme::class,
                'segment'  => Segment::class,
                'wildcard' => Wildcard::class,
            ],
            'factories' => [
                Chain::class    => RouteInvokableFactory::class,
                Hostname::class => RouteInvokableFactory::class,
                Literal::class  => RouteInvokableFactory::class,
                Method::class   => RouteInvokableFactory::class,
                Part::class     => RouteInvokableFactory::class,
                Regex::class    => RouteInvokableFactory::class,
                Scheme::class   => RouteInvokableFactory::class,
                Segment::class  => RouteInvokableFactory::class,
                Wildcard::class => RouteInvokableFactory::class,
            ],
        ]))->configureServiceManager($manager);

        return $manager;
    }

    /**
     * @test
     * @dataProvider invalidRouteConfigurationProvider
     */
    public function willThrowExceptionOnRouteWithOptionalPartAndChildRoutes(array $routes) : void
    {
        $this->expectException(InvalidRouteConfigurationException::class);
        $converter = new ZendRouterV2Converter(
            $this->configurationWithoutBlacklistMock()->reveal(),
            $this->routePluginManager()
        );
        $converter->convert($routes);
    }

    /**
     * @test
     */
    public function willSkipBlacklitedRoute() : void
    {
        $routes = [
            'foo' => [
                'type' => 'literal',
            ],
        ];

        $configuraton = $this->configurationWithoutBlacklistMock();
        $configuraton->getBlacklistedRouteNames()->willReturn(['foo']);
        $converter = new ZendRouterV2Converter($configuraton->reveal(), $this->routePluginManager());

        $this->assertEmpty($converter->convert($routes));
    }

    /**
     * @test
     */
    public function willSkipNestedBlacklistedRoute() : void
    {
        $routes = [
            'foo' => [
                'type'          => 'segment',
                'may_terminate' => false,
                'child_routes'  => [
                    'bar' => [
                        'type' => 'literal',
                    ],
                ],
            ],
        ];

        $configuraton = $this->configurationWithoutBlacklistMock();
        $configuraton->getBlacklistedRouteNames()->willReturn(['foo/bar']);
        $converter = new ZendRouterV2Converter($configuraton->reveal(), $this->routePluginManager());

        $this->assertEmpty($converter->convert($routes));
    }

    /**
     * @param string $type
     *
     * @dataProvider unsupportedRouteTypeProvider
     * @test
     */
    public function willThrowExceptionOnUnsupportedRouteType(string $type) : void
    {
        $converter = new ZendRouterV2Converter(
            $this->configurationWithoutBlacklistMock()->reveal(),
            $this->routePluginManager()
        );

        $routes = [
            $type => [
                'type' => $type,
            ],
        ];

        $this->expectException(InvalidRouteConfigurationException::class);
        $converter->convert($routes);
    }

    public function unsupportedRouteTypeProvider() : array
    {
        return [
            Scheme::class      => [Scheme::class],
            Chain::class       => [Chain::class],
            Part::class        => [Part::class],
            Placeholder::class => [Placeholder::class],
            Regex::class       => [Regex::class],
            Wildcard::class    => [Wildcard::class],
            'whatever'         => ['whatever'],
        ];
    }

    /**
     * @test
     * @dataProvider convertableRoutesProvider
     */
    public function willConvertRoute(array $routes, array $convertedData) : void
    {
        /** @var Route[] $expectedRoutes */
        $expectedRoutes = array_map(function (array $data) : Route {
            $route = new Route($data['path'], new DummyMiddleware(), $data['allowed_methods'], $data['name']);
            $route->setOptions($data['options']);

            return $route;
        }, $convertedData);

        $configuration = $this->configurationWithoutBlacklistMock();
        $converter     = new ZendRouterV2Converter(
            $configuration->reveal(),
            $this->routePluginManager()
        );

        $converted = $converter->convert($routes);

        $this->assertCount(count($expectedRoutes), $converted);

        foreach ($converted as $index => $route) {
            $expected = $expectedRoutes[$index] ?? null;
            $this->assertInstanceOf(Route::class, $expected);
            $this->assertInstanceOf(Route::class, $route);
            $this->assertSame($expected->getName(), $route->getName());
            $this->assertSame($expected->getPath(), $route->getPath());
            $this->assertSame($expected->getOptions(), $route->getOptions());
            $this->assertSame($expected->getAllowedMethods(), $route->getAllowedMethods());
        }
    }

    public function convertableRoutesProvider() : array
    {
        return [
            'simple'                                     => [
                [
                    'foo' => [
                        'type'    => 'literal',
                        'options' => [
                            'route' => '/foo',
                        ],
                    ],
                ],
                [
                    [
                        'name'            => 'foo',
                        'path'            => '/foo',
                        'options'         => [
                            'defaults' => [],
                        ],
                        'allowed_methods' => ZendRouterV2Converter::ANY_REQUEST_METHOD,
                    ],
                ],
            ],
            'simple hostname'                            => [
                [
                    'hostname' => [
                        'type'    => 'hostname',
                        'options' => [
                            'route' => 'www.example.org',
                        ],
                    ],
                ],
                [
                    [
                        'name'            => 'hostname',
                        'path'            => '',
                        'options'         => [
                            'defaults' => [
                                ConverterInterface::HOSTNAME => 'www.example.org',
                            ],
                        ],
                        'allowed_methods' => ZendRouterV2Converter::ANY_REQUEST_METHOD,
                    ],
                ],
            ],
            'hostname_with_children'                     => [
                [
                    'hostname' => [
                        'type'          => 'hostname',
                        'options'       => [
                            'route' => 'www.example.org',
                        ],
                        'may_terminate' => false,
                        'child_routes'  => [
                            'home' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route' => '/home',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    [
                        'name'            => 'hostname/home',
                        'path'            => '/home',
                        'options'         => [
                            'defaults' => [
                                ConverterInterface::HOSTNAME => 'www.example.org',
                            ],
                        ],
                        'allowed_methods' => ZendRouterV2Converter::ANY_REQUEST_METHOD,
                    ],
                ],
            ],
            'segment'                                    => [
                [
                    'segment' => [
                        'type'    => 'segment',
                        'options' => [
                            'route'       => '/foo[/:bar]',
                            'constraints' => [
                                'bar' => '\w+',
                            ],
                            'defaults'    => [
                                'bar' => 'baz',
                            ],
                        ],
                    ],
                ],
                [
                    [
                        'name'            => 'segment',
                        'path'            => '/foo[/{bar:\w+}]',
                        'options'         => [
                            'defaults' => [
                                'bar' => 'baz',
                            ],
                        ],
                        'allowed_methods' => ZendRouterV2Converter::ANY_REQUEST_METHOD,
                    ],
                ],
            ],
            'method specific REST endpoints'             => [
                [
                    'rest' => [
                        'type'          => 'segment',
                        'options'       => [
                            'route'    => '/foo',
                            'defaults' => [
                                'controller' => 'Foo',
                            ],
                        ],
                        'may_terminate' => false,
                        'child_routes'  => [
                            'create' => [
                                'type'    => 'method',
                                'options' => [
                                    'verb'     => 'post',
                                    'defaults' => [
                                        'action' => 'create',
                                    ],
                                ],
                            ],
                            'list'   => [
                                'type'    => 'method',
                                'options' => [
                                    'verb'     => 'get',
                                    'defaults' => [
                                        'action' => 'list',
                                    ],
                                ],
                            ],
                            'entity' => [
                                'type'          => 'segment',
                                'options'       => [
                                    'route' => '/:id',
                                ],
                                'may_terminate' => false,
                                'child_routes'  => [
                                    'show'   => [
                                        'type'    => 'method',
                                        'options' => [
                                            'verb'     => 'get',
                                            'defaults' => [
                                                'action' => 'fetch',
                                            ],
                                        ],
                                    ],
                                    'delete' => [
                                        'type'    => 'method',
                                        'options' => [
                                            'verb'     => 'delete',
                                            'defaults' => [
                                                'action' => 'delete',
                                            ],
                                        ],
                                    ],
                                    'update' => [
                                        'type'    => 'method',
                                        'options' => [
                                            'verb'     => 'patch',
                                            'defaults' => [
                                                'action' => 'patch',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    [
                        'name'            => 'rest/create',
                        'path'            => '/foo',
                        'options'         => [
                            'defaults' => [
                                'controller' => 'Foo',
                                'action'     => 'create',
                            ],
                        ],
                        'allowed_methods' => ['POST'],
                    ],
                    [
                        'name'            => 'rest/list',
                        'path'            => '/foo',
                        'options'         => [
                            'defaults' => [
                                'controller' => 'Foo',
                                'action'     => 'list',
                            ],
                        ],
                        'allowed_methods' => ['GET'],
                    ],
                    [
                        'name'            => 'rest/entity/show',
                        'path'            => '/foo/{id:.+}',
                        'options'         => [
                            'defaults' => [
                                'controller' => 'Foo',
                                'action'     => 'fetch',
                            ],
                        ],
                        'allowed_methods' => ['GET'],
                    ],
                    [
                        'name'            => 'rest/entity/delete',
                        'path'            => '/foo/{id:.+}',
                        'options'         => [
                            'defaults' => [
                                'controller' => 'Foo',
                                'action'     => 'delete',
                            ],
                        ],
                        'allowed_methods' => ['DELETE'],
                    ],
                    [
                        'name'            => 'rest/entity/update',
                        'path'            => '/foo/{id:.+}',
                        'options'         => [
                            'defaults' => [
                                'controller' => 'Foo',
                                'action'     => 'patch',
                            ],
                        ],
                        'allowed_methods' => ['PATCH'],
                    ],
                ],
            ],
            'segment with multiple parameters'           => [
                [
                    'segment'                            => [
                        'type'    => 'segment',
                        'options' => [
                            'route' => '/foo/:bar/baz/:qoo',
                        ],
                    ],
                    'segment with ending slash'          => [
                        'type'    => 'segment',
                        'options' => [
                            'route' => '/foo/:bar/baz/:qoo/',
                        ],
                    ],
                    'segment with optional ending slash' => [
                        'type'    => 'segment',
                        'options' => [
                            'route' => '/foo/:bar/baz/:qoo[/]',
                        ],
                    ],
                ],
                [
                    [
                        'name'            => 'segment',
                        'path'            => '/foo/{bar:[^\/]+}/baz/{qoo:.+}',
                        'options'         => [
                            'defaults' => [],
                        ],
                        'allowed_methods' => ZendRouterV2Converter::ANY_REQUEST_METHOD,
                    ],
                    [
                        'name'            => 'segment with ending slash',
                        'path'            => '/foo/{bar:[^\/]+}/baz/{qoo:[^\/]+}/',
                        'options'         => [
                            'defaults' => [],
                        ],
                        'allowed_methods' => ZendRouterV2Converter::ANY_REQUEST_METHOD,
                    ],
                    [
                        'name'            => 'segment with optional ending slash',
                        'path'            => '/foo/{bar:[^\/]+}/baz/{qoo:[^\/]+}[/]',
                        'options'         => [
                            'defaults' => [],
                        ],
                        'allowed_methods' => ZendRouterV2Converter::ANY_REQUEST_METHOD,
                    ],
                ],
            ],
            'routes with priorities'                     => [
                [
                    'foo' => [
                        'type'    => 'literal',
                        'options' => [
                            'route' => '/foo',
                        ],
                    ],
                    'bar' => [
                        'type'     => 'literal',
                        'options'  => [
                            'route' => '/foo',
                        ],
                        'priority' => 2,
                    ],
                ],
                [
                    [
                        'name'            => 'bar',
                        'path'            => '/foo',
                        'options'         => [
                            'defaults' => [],
                        ],
                        'allowed_methods' => ZendRouterV2Converter::ANY_REQUEST_METHOD,
                    ],
                    [
                        'name'            => 'foo',
                        'path'            => '/foo',
                        'options'         => [
                            'defaults' => [],
                        ],
                        'allowed_methods' => ZendRouterV2Converter::ANY_REQUEST_METHOD,
                    ],
                ],
            ],
            'routes with parameter name quite identical' => [
                [
                    'foo' => [
                        'type'    => 'segment',
                        'options' => [
                            'route' => '/foo/:bar/:bar_id',
                        ],
                    ],
                ],
                [
                    [
                        'name'            => 'foo',
                        'path'            => '/foo/{bar:[^\/]+}/{bar_id:.+}',
                        'options'         => [
                            'defaults' => [],
                        ],
                        'allowed_methods' => ZendRouterV2Converter::ANY_REQUEST_METHOD,
                    ],
                ],
            ],
        ];
    }

    public function invalidRouteConfigurationProvider()
    {
        return [
            'generic route with optional parameter' => [
                [
                    'foo' => [
                        'type'    => 'segment',
                        'options' => [
                            'route' => '/bar[:baz]/qoo',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function setUp() : void
    {
        parent::setUp();
    }
}
