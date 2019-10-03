<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ZendRouterV2Converter\Configuration;
use Boesing\ZendRouterToExpressiveRouter\Router\GenericRoutePluginManagerFactory;
use Interop\Container\ContainerInterface;
use Webmozart\Assert\Assert;
use Zend\Router\RoutePluginManager;
use Zend\ServiceManager\Factory\FactoryInterface;

final class GenericZendRouterConverterFactory implements FactoryInterface
{
    /** @var array */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $routePluginManagerFactory = new GenericRoutePluginManagerFactory();
        $plugins = $routePluginManagerFactory($container, RoutePluginManager::class);
        Assert::isInstanceOf($plugins, RoutePluginManager::class);
        return new ZendRouterV2Converter(
            new Configuration($this->config),
            $plugins
        );
    }
}
