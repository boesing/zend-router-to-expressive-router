<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToFastroute\ExpressiveRouter;

use Boesing\ZendRouterToFastroute\ExpressiveRouter\ZendRouterV2Converter\ConfigurationInterface;
use Interop\Container\ContainerInterface;
use Zend\Router\RoutePluginManager;
use Zend\ServiceManager\Factory\FactoryInterface;

final class ZendRouterV2ConverterFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $configuration = $container->get(ConfigurationInterface::class);
        $plugins       = $container->get(RoutePluginManager::class);

        return new ZendRouterV2Converter($configuration, $plugins);
    }
}
