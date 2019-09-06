<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouterTest\Mvc;

use Boesing\ZendRouterToExpressiveRouter\Mvc\RouteListener;
use Boesing\ZendRouterToExpressiveRouter\Mvc\RouteListenerFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Router\RouterInterface;

final class RouteListenerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function willCreateRouteListenerWithExpectedDependencies() : void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $router    = $this->prophesize(RouterInterface::class);

        $container->get(RouterInterface::class)->willReturn($router->reveal());

        $factory  = new RouteListenerFactory();
        $listener = $factory($container->reveal(), RouteListener::class);
        $this->assertInstanceOf(RouteListener::class, $listener);
    }
}
