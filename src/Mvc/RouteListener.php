<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\Mvc;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ConverterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventsCapableInterface;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;
use Zend\Http\Request;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Psr7Bridge\Psr7ServerRequest;
use Zend\Router\Http\RouteMatch;

final class RouteListener extends AbstractListenerAggregate
{
    /** @var RouterInterface */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @inheritDoc
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'match'], 2);
    }

    /**
     * @return null|RouteMatch|mixed
     */
    public function match(MvcEvent $event)
    {
        $request = $event->getRequest();
        if (! $request instanceof Request) {
            return null;
        }

        $routeResult = $this->matchAndApplyZendRouterLogic(Psr7ServerRequest::fromZend($request));

        if ($routeResult->isFailure()) {
            return $this->handleRouteNotFound($event);
        }

        $match = new RouteMatch($routeResult->getMatchedParams());
        $match->setMatchedRouteName((string) $routeResult->getMatchedRouteName());
        $event->setRouteMatch($match);

        // Stop propagation to avoid handling of zend-mvc RouteListener
        $event->stopPropagation();

        return $match;
    }

    private function matchAndApplyZendRouterLogic(ServerRequestInterface $request) : RouteResult
    {
        $routeResult = $this->router->match($request);
        if ($routeResult->isFailure()) {
            return $routeResult;
        }

        $params   = $routeResult->getMatchedParams();
        $hostname = $params[ConverterInterface::HOSTNAME] ?? null;
        if (! $this->hostnameMatchesRequest($request, $hostname)) {
            return RouteResult::fromRouteFailure(null);
        }

        return $routeResult;
    }

    private function hostnameMatchesRequest(ServerRequestInterface $request, ?string $hostname) : bool
    {
        if ($hostname === null) {
            return true;
        }

        return $request->getUri()->getHost() === $hostname;
    }

    /**
     * In here, we are trying to mimic the logic from zend-mvc {@see \Zend\Mvc\RouteListener}.
     * We try to trigger the event manager with the dispatch error event, so that the RouteListener of zend-mvc does not
     * have to deal with this.
     */
    private function handleRouteNotFound(MvcEvent $event)
    {
        $target = $event->getTarget();

        // Cannot handle 404 state, let zend-mvc deal with it
        if (! $target instanceof EventsCapableInterface) {
            return null;
        }

        $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
        $event->setError(Application::ERROR_ROUTER_NO_MATCH);
        $event->stopPropagation(false);
        $eventManager = $target->getEventManager();

        $results = $eventManager->triggerEvent($event);
        $event->stopPropagation();
        if (! empty($results)) {
            return $results->last();
        }

        return $event->getParams();
    }
}
