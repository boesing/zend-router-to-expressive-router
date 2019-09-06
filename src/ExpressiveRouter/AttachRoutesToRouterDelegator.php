<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToFastroute\ExpressiveRouter;

use Interop\Container\ContainerInterface;
use Webmozart\Assert\Assert;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouterInterface;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;

use function is_array;
use function sprintf;

final class AttachRoutesToRouterDelegator implements DelegatorFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, ?array $options = null)
    {
        $router = $callback();
        if (! $router instanceof RouterInterface) {
            throw new InvalidServiceException(sprintf(
                'You are using this delegator wrong. Please ensure that its only being used for %s',
                RouterInterface::class
            ));
        }

        $routes = $container->get('config')['routes'] ?? [];
        if (empty($routes) || ! is_array($routes)) {
            return $router;
        }

        Assert::allIsInstanceOf($routes, Route::class);
        foreach ($routes as $route) {
            $router->addRoute($route);
        }

        return $router;
    }
}
