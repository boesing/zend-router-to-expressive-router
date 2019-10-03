<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ZendRouterV2Converter\ConfigurationInterface;
use Boesing\ZendRouterToExpressiveRouter\Middleware\DummyMiddleware;
use Zend\Expressive\Router\Route;
use Zend\Router\Http\Hostname;
use Zend\Router\Http\Literal;
use Zend\Router\Http\Method;
use Zend\Router\Http\Regex;
use Zend\Router\Http\Segment;
use Zend\Router\RoutePluginManager;

use function array_keys;
use function array_merge;
use function array_values;
use function count;
use function in_array;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function preg_split;
use function rtrim;
use function sprintf;
use function strlen;

final class ZendRouterV2Converter implements ConverterInterface
{
    public const ANY_REQUEST_METHOD = null;

    /** @see https://github.com/nikic/FastRoute/blob/89aca17bc275eeb2418eecf8301b372f1e772b22/src/RouteParser/Std.php#L26 */
    private const VARIABLE_REGEX = <<<'REGEX'
\{
    \s* ([a-zA-Z_][a-zA-Z0-9_-]*) \s*
    (?:
        : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*)
    )?
\}
REGEX;

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

            if ($this->containsUnsupportedOptionalRouteParts($metadata)) {
                throw InvalidRouteConfigurationException::fromUnsupportedOptionalRoutePart(
                    $metadata->name(),
                    $metadata->path()
                );
            }

            $metadata = $this->pluginSpecificInformations($metadata);

            if (! $metadata->children) {
                if (! $metadata->terminates) {
                    throw InvalidRouteConfigurationException::fromUnreachableRoute($metadata->name());
                }

                $flattened[] = $metadata;
                continue;
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

        if ($plugin instanceof Regex) {
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
        if ($metadata->requestMethods) {
            $requestMethods = $metadata->requestMethods;
        }

        $route = new CacheableRoute(
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

        $matches = [];
        preg_match_all('#:(?<parameters>\w+)#', $path, $matches);
        $parameters = $matches['parameters'] ?? [];

        if (empty($parameters)) {
            return $path;
        }

        $searchAndReplace = [];
        foreach ($parameters as $parameter) {
            $parameterValue                 = sprintf('%s:%s', $parameter, $metadata->constraint($parameter));
            $searchValue                    = sprintf('#:\b%s\b#', preg_quote($parameter, '#'));
            $searchAndReplace[$searchValue] = sprintf('{%s}', $parameterValue);
        }

        return preg_replace(array_keys($searchAndReplace), array_values($searchAndReplace), $path);
    }

    /**
     * This method is inspired by the std routeparser of nikitas fastroute router.
     */
    private function containsUnsupportedOptionalRouteParts(RouteMetadata $metadata)
    {
        $path = $metadata->path();

        $routeWithoutClosingOptionals = rtrim($path, ']');
        $numOptionals                 = strlen($path) - strlen($routeWithoutClosingOptionals);

        // Split on [ while skipping placeholders
        $segments = preg_split('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \[~x', $routeWithoutClosingOptionals);
        if ($numOptionals !== count($segments) - 1) {
            // If there are any ] in the middle of the route, throw a more specific error message
            if (preg_match('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \]~x', $routeWithoutClosingOptionals)) {
                return true;
            }

            return true;
        }

        foreach ($segments as $n => $segment) {
            if ($segment === '' && $n !== 0) {
                return true;
            }
        }

        return false;
    }
}
