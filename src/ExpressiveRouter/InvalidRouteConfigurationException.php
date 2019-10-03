<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter;

use RuntimeException;

use function sprintf;

final class InvalidRouteConfigurationException extends RuntimeException
{
    private function __construct($message)
    {
        parent::__construct($message);
    }

    public static function fromUnreachableRoute(string $routeName) : self
    {
        return new self(sprintf(
            'Found route with name "%s" which is unreachable due to missing `child_routes` and `may_terminate` false configuration.',
            $routeName
        ));
    }

    public static function fromUnsupportedOptionalRoutePart(string $routeName, string $route) : self
    {
        return new self(sprintf(
            'Route "%s" contains unsupported optional part in its path. Cannot create a proper route for all child routes in that case: %s',
            $routeName,
            $route
        ));
    }

    public static function fromUnsupportedRouteType(string $routeName, string $routeType) : self
    {
        return new self(sprintf(
            'Route "%s" has invalid type %s.',
            $routeName,
            $routeType
        ));
    }

    public static function fromUnsupportedRegexRoute(string $regex) : self
    {
        return new self(sprintf(
            'Provided regex route "%s" is not supported.',
            $regex
        ));
    }
}
