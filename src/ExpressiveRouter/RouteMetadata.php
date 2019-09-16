<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter;

use Webmozart\Assert\Assert;
use Zend\Router\Http\RouteInterface;
use Zend\Router\RoutePluginManager;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

use function array_keys;
use function array_replace_recursive;
use function sprintf;
use function strtoupper;
use function usort;

final class RouteMetadata
{
    /** @var string */
    public $path;

    /** @var string */
    public $requestMethod;

    /** @var array<string,string> */
    public $constraints = [];

    /** @var string */
    public $type;

    /** @var RouteMetadata[] */
    public $children = [];

    /** @var bool */
    public $terminates;

    /** @var array<string,mixed> */
    private $defaults = [];

    /** @var string */
    private $name;

    /** @var RouteMetadata|null */
    private $parent;

    /** @var int|null */
    private $priority;

    private function __construct(string $name, string $type, string $requestMethod, string $path, bool $terminates)
    {
        $this->name          = $name;
        $this->type          = $type;
        $this->requestMethod = $requestMethod;
        $this->path          = $path;
        $this->terminates    = $terminates;
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
            $metadata = self::fromZendRouterRouteConfiguration($route, $details);
            if ($parent) {
                $metadata = $metadata->withParent($parent);
            }

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
        $terminates    = $config['may_terminate'] ?? true;
        $requestMethod = strtoupper($options['verb'] ?? '');

        $instance = new self($name, $type, $requestMethod, $route, $terminates);

        $instance->defaults    = $options['defaults'] ?? [];
        $instance->constraints = $options['constraints'] ?? [];
        $instance->priority    = $config['priority'] ?? null;

        return $instance->withChildren($config['child_routes'] ?? []);
    }

    private function withChildren(array $children) : self
    {
        $this->children = self::collection($children, $this);

        return $this;
    }

    private function withParent(RouteMetadata $parent) : self
    {
        $this->parent = $parent;

        return $this;
    }

    public function convertedRouteType(RoutePluginManager $plugins) : RouteInterface
    {
        try {
            return $plugins->get($this->type, ['route' => '', 'verb' => '']);
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

    public function path() : string
    {
        if ($this->parent) {
            return $this->parent->path() . $this->path;
        }

        return $this->path;
    }

    public function defaults() : array
    {
        if ($this->parent) {
            return (array) array_replace_recursive($this->parent->defaults(), $this->defaults);
        }

        return $this->defaults;
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
}
