<?php
declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter;

use Zend\Expressive\Router\Route;

final class CacheableRoute extends Route
{

    public static function __set_state(array $properties): Route
    {
        return new Route($properties['path'], $properties['middleware'], $properties['methods'], $properties['name']);
    }
}
