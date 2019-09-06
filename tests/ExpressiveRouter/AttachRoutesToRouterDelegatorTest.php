<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToFastrouteTest\ExpressiveRouter;

use Boesing\ZendRouterToFastroute\ExpressiveRouter\AttachRoutesToRouterDelegator;
use Boesing\ZendRouterToFastroute\Middleware\DummyMiddleware;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use stdClass;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouterInterface;
use Zend\ServiceManager\Exception\InvalidServiceException;

final class AttachRoutesToRouterDelegatorTest extends TestCase
{
    /**
     * @test
     */
    public function willThrowInvalidServiceExceptionIfDelegatorUsedForNonRouterInterfaceServices() : void
    {
        $this->expectException(InvalidServiceException::class);

        $callback = function () : stdClass {
            return new stdClass();
        };

        $container = $this->prophesize(ContainerInterface::class);

        $delegator = new AttachRoutesToRouterDelegator();
        $delegator($container->reveal(), RouterInterface::class, $callback);
    }

    /**
     * @test
     */
    public function willAttachRoutesFromConfigToRouter() : void
    {
        $route  = new Route('', new DummyMiddleware(), null, '');
        $routes = [$route];

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('addRoute')
            ->with($route);

        $callback = function () use ($router) : RouterInterface {
            return $router;
        };

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn(['routes' => $routes]);
        $delegator = new AttachRoutesToRouterDelegator();
        $delegator($container->reveal(), RouterInterface::class, $callback);
    }
}
