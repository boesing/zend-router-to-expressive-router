<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToFastroute\ModuleManager;

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
        $configListener = $container->get(ConfigListener::class);
        $configListener->attach($eventManager);

        return $moduleManager;
    }
}
