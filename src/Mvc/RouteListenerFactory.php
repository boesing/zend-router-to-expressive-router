<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\Mvc;

use Interop\Container\ContainerInterface;
use Zend\Expressive\Router\RouterInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

final class RouteListenerFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $router = $container->get(RouterInterface::class);

        return new RouteListener($router);
    }
}
