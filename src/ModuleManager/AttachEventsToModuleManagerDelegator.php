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
    /** @var ConfigListener */
    private $listener;

    public function __construct(ConfigListener $listener)
    {
        $this->listener = $listener;
    }

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

        $eventManager = $moduleManager->getEventManager();
        $this->listener->attach($eventManager);

        return $moduleManager;
    }
}
