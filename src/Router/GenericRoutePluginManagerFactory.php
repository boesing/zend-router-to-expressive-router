<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\Router;

use Interop\Container\ContainerInterface;
use Zend\Router\Http\Chain;
use Zend\Router\Http\Hostname;
use Zend\Router\Http\Literal;
use Zend\Router\Http\Method;
use Zend\Router\Http\Part;
use Zend\Router\Http\Regex;
use Zend\Router\Http\Scheme;
use Zend\Router\Http\Segment;
use Zend\Router\Http\Wildcard;
use Zend\Router\RouteInvokableFactory;
use Zend\Router\RoutePluginManager;
use Zend\ServiceManager\Factory\FactoryInterface;

final class GenericRoutePluginManagerFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new RoutePluginManager($container, [
            'aliases'   => [
                'chain'    => Chain::class,
                'Chain'    => Chain::class,
                'hostname' => Hostname::class,
                'Hostname' => Hostname::class,
                'hostName' => Hostname::class,
                'HostName' => Hostname::class,
                'literal'  => Literal::class,
                'Literal'  => Literal::class,
                'method'   => Method::class,
                'Method'   => Method::class,
                'part'     => Part::class,
                'Part'     => Part::class,
                'regex'    => Regex::class,
                'Regex'    => Regex::class,
                'scheme'   => Scheme::class,
                'Scheme'   => Scheme::class,
                'segment'  => Segment::class,
                'Segment'  => Segment::class,
                'wildcard' => Wildcard::class,
                'Wildcard' => Wildcard::class,
                'wildCard' => Wildcard::class,
                'WildCard' => Wildcard::class,
            ],
            'factories' => [
                Chain::class    => RouteInvokableFactory::class,
                Hostname::class => RouteInvokableFactory::class,
                Literal::class  => RouteInvokableFactory::class,
                Method::class   => RouteInvokableFactory::class,
                Part::class     => RouteInvokableFactory::class,
                Regex::class    => RouteInvokableFactory::class,
                Scheme::class   => RouteInvokableFactory::class,
                Segment::class  => RouteInvokableFactory::class,
                Wildcard::class => RouteInvokableFactory::class,
                // v2 normalized names
                'zendmvcrouterhttpchain'    => RouteInvokableFactory::class,
                'zendmvcrouterhttphostname' => RouteInvokableFactory::class,
                'zendmvcrouterhttpliteral'  => RouteInvokableFactory::class,
                'zendmvcrouterhttpmethod'   => RouteInvokableFactory::class,
                'zendmvcrouterhttppart'     => RouteInvokableFactory::class,
                'zendmvcrouterhttpregex'    => RouteInvokableFactory::class,
                'zendmvcrouterhttpscheme'   => RouteInvokableFactory::class,
                'zendmvcrouterhttpsegment'  => RouteInvokableFactory::class,
                'zendmvcrouterhttpwildcard' => RouteInvokableFactory::class,
            ],
        ]);
    }
}
