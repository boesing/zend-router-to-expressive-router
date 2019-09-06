<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToFastroute;

use Boesing\ZendRouterToFastroute\ExpressiveRouter\AttachRoutesToRouterDelegator;
use Boesing\ZendRouterToFastroute\ExpressiveRouter\ConverterInterface;
use Boesing\ZendRouterToFastroute\ExpressiveRouter\ZendRouterV2Converter;
use Boesing\ZendRouterToFastroute\ExpressiveRouter\ZendRouterV2ConverterFactory;
use Boesing\ZendRouterToFastroute\ModuleManager\ConfigListener;
use Boesing\ZendRouterToFastroute\ModuleManager\ConfigListenerFactory;
use Boesing\ZendRouterToFastroute\Mvc\RouteListener;
use Boesing\ZendRouterToFastroute\Mvc\RouteListenerFactory;
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
            'router'   => [
                'fastroute' => [],
            ],
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
                RouterInterface::class    => FastRouteRouter::class,
                ConverterInterface::class => ZendRouterV2Converter::class,
            ],
            'factories'  => [
                ConfigListener::class                      => ConfigListenerFactory::class,
                RouteListener::class                       => RouteListenerFactory::class,
                FastRouteRouter::class                     => FastRouteRouterFactory::class,
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
