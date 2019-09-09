<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouterTest\ModuleManager;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ConverterInterface;
use Boesing\ZendRouterToExpressiveRouter\ModuleManager\AttachEventsToModuleManagerDelegator;
use Boesing\ZendRouterToExpressiveRouter\ModuleManager\ConfigListener;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use stdClass;
use Zend\EventManager\EventManagerInterface;
use Zend\ModuleManager\ModuleManager;
use Zend\ServiceManager\Exception\InvalidServiceException;

final class AttachEventsToModuleManagerDelegatorTest extends TestCase
{
    /**
     * @test
     */
    public function willThrowInvalidServiceExceptionIfDelegatorUsedWrong() : void
    {
        $this->expectException(InvalidServiceException::class);
        $callback  = function () : stdClass {
            return new stdClass();
        };
        $container = $this->prophesize(ContainerInterface::class);

        $delegator = new AttachEventsToModuleManagerDelegator();
        $delegator($container->reveal(), 'foo', $callback);
    }

    /**
     * @test
     */
    public function willAttachConfigListenerToEvents() : void
    {
        $converter      = $this->prophesize(ConverterInterface::class)->reveal();
        $configListener = new ConfigListener($converter);
        $events         = $this->prophesize(EventManagerInterface::class);

        $moduleManager = $this->prophesize(ModuleManager::class);
        $moduleManager->getEventManager()->willReturn($events->reveal());

        $container = $this->prophesize(ContainerInterface::class);
        $container->has(ConfigListener::class)->willReturn(true);
        $container->get(ConfigListener::class)->willReturn($configListener);

        $moduleManager = $moduleManager->reveal();

        $callback = function () use ($moduleManager) : ModuleManager {
            return $moduleManager;
        };

        $delegator = new AttachEventsToModuleManagerDelegator();
        $this->assertSame($moduleManager, $delegator($container->reveal(), 'bar', $callback));
    }

    /**
     * @test
     */
    public function willCreateConfigListenerFromFactoryIfUsedInApplicationConfiguration() : void
    {
        $events    = $this->prophesize(EventManagerInterface::class)->reveal();
        $converter = $this->prophesize(ConverterInterface::class)->reveal();

        $moduleManager = $this->prophesize(ModuleManager::class);
        $moduleManager->getEventManager()->willReturn($events);

        $container = $this->prophesize(ContainerInterface::class);
        $container->has(ConfigListener::class)->willReturn(false);
        $container->get(ConverterInterface::class)->willReturn($converter);

        $moduleManager = $moduleManager->reveal();

        $callback = function () use ($moduleManager) : ModuleManager {
            return $moduleManager;
        };

        $delegator = new AttachEventsToModuleManagerDelegator();
        $this->assertSame($moduleManager, $delegator($container->reveal(), 'bar', $callback));
    }
}
