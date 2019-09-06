<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToFastrouteTest\ModuleManager;

use Boesing\ZendRouterToFastroute\ExpressiveRouter\ConverterInterface;
use Boesing\ZendRouterToFastroute\ModuleManager\ConfigListener;
use Boesing\ZendRouterToFastroute\ModuleManager\ConfigListenerFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

final class ConfigListenerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function willCreateConfigListenerWithExpectedDependencies() : void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $converter = $this->prophesize(ConverterInterface::class);

        $container->get(ConverterInterface::class)->willReturn($converter->reveal());

        $factory  = new ConfigListenerFactory();
        $listener = $factory($container->reveal(), ConfigListener::class);
        $this->assertInstanceOf(ConfigListener::class, $listener);
    }
}
