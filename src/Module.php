<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\AttachRoutesToRouterDelegator;
use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ConverterInterface;
use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ZendRouterV2Converter;
use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ZendRouterV2ConverterFactory;
use Boesing\ZendRouterToExpressiveRouter\ModuleManager\ConfigListener;
use Boesing\ZendRouterToExpressiveRouter\ModuleManager\ConfigListenerFactory;
use Boesing\ZendRouterToExpressiveRouter\Mvc\RouteListener;
use Boesing\ZendRouterToExpressiveRouter\Mvc\RouteListenerFactory;
use Zend\Expressive\Router\FastRouteRouter;
use Zend\Expressive\Router\FastRouteRouterFactory;
use Zend\Expressive\Router\RouterInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;

final class Module implements ServiceProviderInterface, ConfigProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        return [
            'listener' => [
                RouteListener::class,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getServiceConfig()
    {
        return [
            'aliases'    => [
                ConverterInterface::class => ZendRouterV2Converter::class,
            ],
            'factories'  => [
                ConfigListener::class                      => ConfigListenerFactory::class,
                RouteListener::class                       => RouteListenerFactory::class,
                ZendRouterV2Converter::class               => ZendRouterV2ConverterFactory::class,
                ZendRouterV2Converter\Configuration::class => ZendRouterV2Converter\ConfigurationFactory::class,
            ],
            'delegators' => [
                RouterInterface::class => [
                    'routes' => AttachRoutesToRouterDelegator::class,
                ],
            ],
        ];
    }
}
