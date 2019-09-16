<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\AttachRoutesToRouterDelegator;
use Boesing\ZendRouterToExpressiveRouter\Mvc\RouteListener;
use Boesing\ZendRouterToExpressiveRouter\Mvc\RouteListenerFactory;
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
            'listeners' => [
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
            'factories'  => [
                RouteListener::class => RouteListenerFactory::class,
            ],
            'delegators' => [
                RouterInterface::class => [
                    'routes' => AttachRoutesToRouterDelegator::class,
                ],
            ],
        ];
    }
}
