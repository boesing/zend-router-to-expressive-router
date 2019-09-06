<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\ModuleManager;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ConverterInterface;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

final class ConfigListenerFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $converter = $container->get(ConverterInterface::class);

        return new ConfigListener($converter);
    }
}
