<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToFastroute\ExpressiveRouter\ZendRouterV2Converter;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

final class ConfigurationFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $configuration = $container->get('config')[Configuration::CONFIG_KEY] ?? [];

        return new Configuration($configuration);
    }
}
