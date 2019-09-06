<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ZendRouterV2Converter\ConfigurationInterface;
use Boesing\ZendRouterToExpressiveRouter\Middleware\DummyMiddleware;
use Zend\Expressive\Router\Route;
use Zend\Router\Http\Hostname;
use Zend\Router\Http\Literal;
use Zend\Router\Http\Method;
use Zend\Router\Http\Segment;
use Zend\Router\RoutePluginManager;

use function array_keys;
use function array_merge;
use function array_values;
use function in_array;
use function preg_match;
use function preg_match_all;
use function sprintf;
use function str_replace;

class ZendRouterV2Converter implements ConverterInterface
{
    public const ANY_REQUEST_METHOD = null;

    /** @var ConfigurationInterface */
    private $configuration;

    /** @var RoutePluginManager */
    private $plugins;

    public function __construct(ConfigurationInterface $configuration, RoutePluginManager $plugins)
    {
        $this->configuration = $configuration;
        $this->plugins       = $plugins;
    }

    /**
     * @return Route[]
     */
    public function convert(array $routes) : array
    {
        $metadataCollection = RouteMetadata::collection($routes);
        $flattened          = $this->flattenRoutes($metadataCollection);
        $converted          = [];
        foreach ($flattened as $metadata) {
            $converted[] = $this->metadataToExpressiveRouterRoute($metadata);
        }

        return $converted;
    }

    /**
     * @param RouteMetadata[] $routes
     *
     * @return RouteMetadata[]
     */
    private function flattenRoutes(array $routes) : array
    {
        $flattened = [];
        foreach ($routes as $metadata) {
            if ($this->isRouteNameBlacklisted($metadata->name())) {
                continue;
            }

            if (! $this->isRouteTypeSupported($metadata)) {
                throw InvalidRouteConfigurationException::fromUnsupportedRouteType($metadata->name(), $metadata->type);
            }

            $metadata = $this->pluginSpecificInformations($metadata);

            if (! $metadata->children) {
                if (! $metadata->terminates) {
                    throw InvalidRouteConfigurationException::fromUnreachableRoute($metadata->name());
                }

                $flattened[] = $metadata;
                continue;
            }

            if (preg_match('#[\]\[]#', $metadata->path)) {
                throw InvalidRouteConfigurationException::fromUnsupportedOptionalRoutePart(
                    $metadata->name(),
                    $metadata->path
                );
            }

            if ($metadata->terminates) {
                $flattened[] = $metadata;
            }

            $flattened = array_merge($flattened, $this->flattenRoutes($metadata->children));
        }

        return $flattened;
    }

    private function isRouteNameBlacklisted(string $routeName) : bool
    {
        return in_array($routeName, $this->configuration->getBlacklistedRouteNames(), true);
    }

    private function isRouteTypeSupported(RouteMetadata $metadata) : bool
    {
        $plugin = $metadata->convertedRouteType($this->plugins);

        if ($plugin instanceof Segment) {
            return true;
        }

        if ($plugin instanceof Literal) {
            return true;
        }

        if ($plugin instanceof Method) {
            return true;
        }

        if ($plugin instanceof Hostname) {
            return true;
        }

        return false;
    }

    private function pluginSpecificInformations(RouteMetadata $metadata) : RouteMetadata
    {
        $plugin = $metadata->convertedRouteType($this->plugins);
        if ($plugin instanceof Hostname) {
            return $this->hostname($metadata);
        }

        return $metadata;
    }

    private function hostname(RouteMetadata $metadata) : RouteMetadata
    {
        $hostname       = $metadata->path;
        $metadata->path = '';
        $metadata       = $metadata->withDefault(ConverterInterface::HOSTNAME, $hostname);

        return $metadata;
    }

    private function metadataToExpressiveRouterRoute(RouteMetadata $metadata) : Route
    {
        $requestMethods = Route::HTTP_METHOD_ANY;
        if ($metadata->requestMethod) {
            $requestMethods = [$metadata->requestMethod];
        }

        $route = new Route(
            $this->convertRouteAndHandleParameters($metadata),
            new DummyMiddleware(),
            $requestMethods,
            $metadata->name()
        );

        $route->setOptions(['defaults' => $metadata->defaults()]);

        return $route;
    }

    private function convertRouteAndHandleParameters(RouteMetadata $metadata) : string
    {
        $path = $metadata->path();

        $parameters = [];
        preg_match_all('#:(\w+)#', $path, $parameters);
        if (empty($parameters[0])) {
            return $path;
        }

        $constraints = $metadata->constraints;

        $searchAndReplace = [];
        foreach ($parameters[1] as $parameter) {
            $parameterValue = $parameter;
            if (isset($constraints[$parameter])) {
                $parameterValue = sprintf('%s:%s', $parameter, $constraints[$parameter]);
            }

            $searchAndReplace[':' . $parameter] = sprintf('{%s}', $parameterValue);
        }

        return str_replace(array_keys($searchAndReplace), array_values($searchAndReplace), $path);
    }
}
