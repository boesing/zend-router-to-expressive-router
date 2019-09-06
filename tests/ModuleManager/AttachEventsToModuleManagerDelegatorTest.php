<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToFastrouteTest\ModuleManager;

use Boesing\ZendRouterToFastroute\ExpressiveRouter\ConverterInterface;
use Boesing\ZendRouterToFastroute\ModuleManager\AttachEventsToModuleManagerDelegator;
use Boesing\ZendRouterToFastroute\ModuleManager\ConfigListener;
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
    public function willThrowInvalidServiceExceptionIfDelegatorUsedWrong()
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
    public function willAttachConfigListenerToEvents()
    {
        $events         = $this->prophesize(EventManagerInterface::class)->reveal();
        $converter      = $this->prophesize(ConverterInterface::class)->reveal();
        $configListener = new ConfigListener($converter);
        $configListener->attach($events);

        $moduleManager = $this->prophesize(ModuleManager::class);
        $moduleManager->getEventManager()->willReturn($events);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(ConfigListener::class)->willReturn($configListener);

        $moduleManager = $moduleManager->reveal();

        $callback = function () use ($moduleManager) : ModuleManager {
            return $moduleManager;
        };

        $delegator = new AttachEventsToModuleManagerDelegator();
        $this->assertSame($moduleManager, $delegator($container->reveal(), 'bar', $callback));
    }
}
