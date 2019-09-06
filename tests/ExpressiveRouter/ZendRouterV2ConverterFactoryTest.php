<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToFastrouteTest\ExpressiveRouter;

use Boesing\ZendRouterToFastroute\ExpressiveRouter\ZendRouterV2Converter;
use Boesing\ZendRouterToFastroute\ExpressiveRouter\ZendRouterV2Converter\ConfigurationInterface;
use Boesing\ZendRouterToFastroute\ExpressiveRouter\ZendRouterV2ConverterFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Zend\Router\RoutePluginManager;

final class ZendRouterV2ConverterFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function willCreateRouterWithExpectedDependencies() : void
    {
        $container          = $this->prophesize(ContainerInterface::class);
        $configuration      = $this->prophesize(ConfigurationInterface::class);
        $routePluginManager = $this->prophesize(RoutePluginManager::class);

        $container->get(ConfigurationInterface::class)->willReturn($configuration->reveal());
        $container->get(RoutePluginManager::class)->willReturn($routePluginManager->reveal());

        $factory   = new ZendRouterV2ConverterFactory();
        $converter = $factory($container->reveal(), ZendRouterV2Converter::class);
        $this->assertInstanceOf(ZendRouterV2Converter::class, $converter);
    }
}
