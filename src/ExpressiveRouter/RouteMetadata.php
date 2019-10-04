<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter;

use Webmozart\Assert\Assert;
use Zend\Router\Http\RouteInterface;
use Zend\Router\RoutePluginManager;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_replace;
use function array_replace_recursive;
use function count;
use function explode;
use function preg_replace_callback;
use function Safe\array_combine;
use function Safe\preg_match;
use function Safe\preg_match_all;
use function Safe\sprintf;
use function Safe\substr;
use function Safe\usort;
use function str_replace;
use function stripslashes;
use function strpos;
use function strtoupper;
use function trim;

final class RouteMetadata
{
    /** @var string */
    public $path;

    /** @var array<int,string> */
    public $requestMethods;

    /** @var string */
    public $type;

    /** @var RouteMetadata[] */
    public $children = [];

    /** @var bool */
    public $terminates;

    /** @var array<string,string> */
    private $constraints = [];

    /** @var array<string,mixed> */
    private $defaults = [];

    /** @var string */
    private $name;

    /** @var RouteMetadata|null */
    private $parent;

    /** @var int|null */
    private $priority;

    private function __construct(string $name, string $type, array $requestMethods, string $path, bool $terminates)
    {
        $this->name           = $name;
        $this->type           = $type;
        $this->requestMethods = $requestMethods;
        $this->path           = $path;
        $this->terminates     = $terminates;
    }

    /**
     * @param array<string,array> $routes
     *
     * @return array<int,RouteMetadata>
     */
    public static function collection(array $routes, ?RouteMetadata $parent = null) : array
    {
        Assert::allString(array_keys($routes));
        Assert::allIsArray($routes);

        $metadatas = [];
        foreach ($routes as $route => $details) {
            $metadata         = self::fromZendRouterRouteConfiguration($route, $details);
            $metadata->parent = $parent;

            $metadatas[] = $metadata;
        }

        usort($metadatas, function (RouteMetadata $left, RouteMetadata $right) : int {
            return $right->priority() <=> $left->priority();
        });

        return $metadatas;
    }

    public static function fromZendRouterRouteConfiguration(string $name, array $config) : self
    {
        $options = $config['options'] ?? [];
        Assert::isArray($options);

        $route = $options['route'] ?? '';

        $type = $config['type'] ?? '';
        Assert::notEmpty($type, sprintf('Route type is required for route %s', $name));
        $terminates = $config['may_terminate'] ?? true;
        $verb       = strtoupper($options['verb'] ?? '');
        $regex      = $options['regex'] ?? '';

        $constraints = $options['constraints'] ?? [];
        if ($regex) {
            $constraints = self::extractConstraintsFromRegexDefinition($regex);
            $route       = self::convertRegexToSegmentRoute($regex, $constraints);
        }

        $requestMethods = [];
        if ($verb) {
            $requestMethods = array_map('trim', explode(',', $verb));
        }

        $instance = new self($name, $type, $requestMethods, $route, $terminates);

        // Remove capturing groups from constraints
        $instance->constraints = array_map(function (string $constraint) : string {
            return trim($constraint, '()');
        }, $constraints);
        $instance->defaults    = $options['defaults'] ?? [];
        $instance->priority    = $config['priority'] ?? null;

        return $instance->withChildren($config['child_routes'] ?? []);
    }

    private static function extractConstraintsFromRegexDefinition(string $regex) : array
    {
        $matches = [];
        /** @see https://regex101.com/r/jVtyN5/1 */
        if (! preg_match_all('#\(\?<(?<names>[a-zA-Z]+)>(?<constraints>(\(((?>[^()]+)|(?R))*\)|\[((?>[^\[\]]+)|(?R))*\](\+|\{\d?,\d?\})?)?|.*?)\)#', $regex, $matches)) {
            return [];
        }

        $names       = $matches['names'] ?? [];
        $constraints = $matches['constraints'] ?? [];

        if (count($names) !== count($constraints)) {
            throw InvalidRouteConfigurationException::fromUnsupportedRegexRoute($regex);
        }

        return array_combine($matches['names'] ?? [], $matches['constraints'] ?? []);
    }

    private static function convertRegexToSegmentRoute(string $regex, array $constraints) : string
    {
        $replaced = $regex;

        foreach ($constraints as $parameter => $value) {
            $search   = sprintf('(?<%s>%s)', $parameter, $value);
            $replaced = str_replace($search, sprintf(':%s', $parameter), $replaced);
        }

        /** @see https://regexr.com/4m6p7 */
        $replaced = preg_replace_callback(
            '#(?<optionalPart>(\[.*?\]|\/|\(.*?\)))\?#',
            function (array $matches) : string {
                $optionalPart = trim($matches['optionalPart'], '()');
                return sprintf('[%s]', $optionalPart);
            },
            $replaced
        );

        Assert::string($replaced);

        if (strpos($replaced, '?') !== false) {
            throw InvalidRouteConfigurationException::fromUnsupportedRegexRoute($regex);
        }

        return stripslashes($replaced);
    }

    private function withChildren(array $children) : self
    {
        $this->children = self::collection($children, $this);

        return $this;
    }

    public function priority() : int
    {
        $priority = $this->priority;
        if ($priority === null) {
            if ($this->parent) {
                return $this->parent->priority();
            }

            return 0;
        }

        return $priority;
    }

    public function convertedRouteType(RoutePluginManager $plugins) : RouteInterface
    {
        try {
            return $plugins->get($this->type, ['route' => '', 'verb' => '', 'spec' => '', 'regex' => '']);
        } catch (ServiceNotCreatedException | ServiceNotFoundException $exception) {
            throw InvalidRouteConfigurationException::fromUnsupportedRouteType($this->name(), $this->type);
        }
    }

    public function name() : string
    {
        if ($this->parent) {
            return sprintf('%s/%s', $this->parent->name(), $this->name);
        }

        return $this->name;
    }

    public function withDefault(string $default, $value) : self
    {
        $instance                     = clone $this;
        $instance->defaults[$default] = $value;

        return $instance;
    }

    public function __clone()
    {
        $children       = $this->children;
        $this->children = [];

        foreach ($children as $child) {
            $clone         = clone $child;
            $clone->parent = $this;

            $this->children[] = $clone;
        }
    }

    public function defaults() : array
    {
        if ($this->parent) {
            return (array) array_replace_recursive($this->parent->defaults(), $this->defaults);
        }

        return $this->defaults;
    }

    public function constraint(string $parameter) : string
    {
        $constraints = $this->constraints();
        if (array_key_exists($parameter, $constraints)) {
            return $constraints[$parameter];
        }

        $path   = $this->path();
        $search = sprintf('#:%s$#', $parameter);

        if (preg_match($search, $path)) {
            return '.+';
        }

        return '[^\/]+';
    }

    private function constraints() : array
    {
        $constraintsFromParents = $this->parent ? $this->parent->constraints() : [];

        return (array) array_replace($constraintsFromParents, $this->constraints);
    }

    public function path(bool $calledFromChildRoute = false) : string
    {
        $path = $this->path;
        if ($this->parent) {
            $path = $this->parent->path(true) . $path;
        }

        if (! $calledFromChildRoute) {
            return $path;
        }

        // Remove optional trailing slash
        if (substr($path, -3) === '[/]') {
            $path = substr($path, 0, -3) . '/';
        }

        return $path;
    }
}
