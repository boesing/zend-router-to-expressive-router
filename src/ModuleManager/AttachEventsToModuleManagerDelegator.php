<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\ModuleManager;

use Interop\Container\ContainerInterface;
use Zend\ModuleManager\ModuleManager;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;

use function sprintf;

final class AttachEventsToModuleManagerDelegator implements DelegatorFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, ?array $options = null)
    {
        $moduleManager = $callback();
        if (! $moduleManager instanceof ModuleManager) {
            throw new InvalidServiceException(sprintf(
                'You are using this delegator wrong. Please ensure that its only being used for %s',
                ModuleManager::class
            ));
        }

        $eventManager   = $moduleManager->getEventManager();
        $configListener = $this->configListener($container);
        $configListener->attach($eventManager);

        return $moduleManager;
    }

    private function configListener(ContainerInterface $container) : ConfigListener
    {
        // As this delegator is being used before any module is initialized, we have to use the factory directly
        if ($container->has(ConfigListener::class)) {
            return $container->get(ConfigListener::class);
        }

        return (new ConfigListenerFactory())($container, ConfigListener::class);
    }
}
