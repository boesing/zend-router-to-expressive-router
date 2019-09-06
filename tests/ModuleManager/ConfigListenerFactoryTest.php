<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouterTest\ModuleManager;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ConverterInterface;
use Boesing\ZendRouterToExpressiveRouter\ModuleManager\ConfigListener;
use Boesing\ZendRouterToExpressiveRouter\ModuleManager\ConfigListenerFactory;
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
