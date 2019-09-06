<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToFastrouteTest\Mvc;

use Boesing\ZendRouterToFastroute\ExpressiveRouter\ConverterInterface;
use Boesing\ZendRouterToFastroute\Middleware\DummyMiddleware;
use Boesing\ZendRouterToFastroute\Mvc\RouteListener;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventsCapableInterface;
use Zend\EventManager\ResponseCollection;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;
use Zend\Http\Request;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Router\Http\RouteMatch;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\ResponseInterface;

use function ltrim;
use function sprintf;

final class RouteListenerTest extends TestCase
{
    /** @var RouterInterface&MockObject */
    private $router;

    /** @var RouteListener */
    private $listener;

    /**
     * @test
     */
    public function attachesToRouteEvent() : void
    {
        $events = $this->createMock(EventManagerInterface::class);
        $events
            ->expects($this->once())
            ->method('attach')
            ->with(MvcEvent::EVENT_ROUTE);

        $this->listener->attach($events);
    }

    /**
     * @test
     */
    public function wontMatchOnNonHTTPRequests()
    {
        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects($this->never())
            ->method($this->anything());

        $event = $this->createMock(MvcEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->listener->match($event);
    }

    /**
     * @test
     */
    public function willTriggerNotFoundAndReturnsEventParams() : void
    {
        $event   = $this->createMock(MvcEvent::class);
        $request = $this->createRequestMock();
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $event
            ->expects($this->once())
            ->method('setName')
            ->with(MvcEvent::EVENT_DISPATCH_ERROR);

        $event
            ->expects($this->once())
            ->method('setError')
            ->with(Application::ERROR_ROUTER_NO_MATCH);

        $event
            ->expects($this->exactly(2))
            ->method('stopPropagation')
            ->withConsecutive($this->equalTo(false), $this->equalTo(true));

        $this->router
            ->expects($this->once())
            ->method('match')
            ->with($this->callback(function (ServerRequestInterface $request) : bool {
                return true;
            }))
            ->willReturn(RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY));

        $events = $this->createMock(EventManagerInterface::class);
        $events
            ->expects($this->once())
            ->method('triggerEvent')
            ->with($event);

        $target = $this->createMock(EventsCapableInterface::class);

        $target
            ->expects($this->once())
            ->method('getEventManager')
            ->willReturn($events);

        $event
            ->expects($this->once())
            ->method('getTarget')
            ->willReturn($target);

        $event
            ->expects($this->once())
            ->method('getParams')
            ->willReturn(['foo' => 'bar']);

        $returnValue = $this->listener->match($event);
        $this->assertSame(['foo' => 'bar'], $returnValue);
    }

    private function createRequestMock(string $hostname = '', string $path = '') : Request
    {
        $request = $this
            ->getMockBuilder(Request::class)
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $request
            ->expects($this->any())
            ->method('getUriString')
            ->willReturn(sprintf('http://%s/%s', $hostname, ltrim($path, '/')));

        return $request;
    }

    /**
     * @test
     */
    public function willTriggerNotFoundAndReturnsLastResponse() : void
    {
        $event   = $this->createMock(MvcEvent::class);
        $request = $this->createRequestMock();
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $event
            ->expects($this->once())
            ->method('setName')
            ->with(MvcEvent::EVENT_DISPATCH_ERROR);

        $event
            ->expects($this->once())
            ->method('setError')
            ->with(Application::ERROR_ROUTER_NO_MATCH);

        $event
            ->expects($this->exactly(2))
            ->method('stopPropagation')
            ->withConsecutive($this->equalTo(false), $this->equalTo(true));

        $this->router
            ->expects($this->once())
            ->method('match')
            ->with($this->callback(function (ServerRequestInterface $request) : bool {
                return true;
            }))
            ->willReturn(RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY));

        $response = $this->createMock(ResponseInterface::class);

        $responses = $this->createMock(ResponseCollection::class);

        $responses
            ->expects($this->once())
            ->method('last')
            ->willReturn($response);

        $events = $this->createMock(EventManagerInterface::class);
        $events
            ->expects($this->once())
            ->method('triggerEvent')
            ->with($event)
            ->willReturn($responses);

        $target = $this->createMock(EventsCapableInterface::class);
        $target
            ->expects($this->once())
            ->method('getEventManager')
            ->willReturn($events);

        $event
            ->expects($this->once())
            ->method('getTarget')
            ->willReturn($target);

        $event
            ->expects($this->never())
            ->method('getParams');

        $returnValue = $this->listener->match($event);
        $this->assertSame($response, $returnValue);
    }

    /**
     * @test
     */
    public function willEvaluateIfHostnameMatchesRequest()
    {
        $route  = new Route('/', new DummyMiddleware(), null, 'foo');
        $params = [ConverterInterface::HOSTNAME => 'www2.example.org'];

        $this->router
            ->expects($this->once())
            ->method('match')
            ->willReturn(RouteResult::fromRoute($route, $params));

        $request = $this->createRequestMock('www.example.org');
        $event   = $this->createMock(MvcEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $response = $this->createMock(ResponseInterface::class);

        $responses = $this->createMock(ResponseCollection::class);

        $responses
            ->expects($this->once())
            ->method('last')
            ->willReturn($response);

        $events = $this->createMock(EventManagerInterface::class);
        $events
            ->expects($this->once())
            ->method('triggerEvent')
            ->with($event)
            ->willReturn($responses);

        $target = $this->createMock(EventsCapableInterface::class);
        $target
            ->expects($this->once())
            ->method('getEventManager')
            ->willReturn($events);

        $event
            ->expects($this->once())
            ->method('getTarget')
            ->willReturn($target);

        $event
            ->expects($this->never())
            ->method('getParams');

        $returnValue = $this->listener->match($event);
        $this->assertSame($response, $returnValue);
    }

    /**
     * @test
     */
    public function willConvertRouteResultToRouteMatch()
    {
        $route = new Route('/', new DummyMiddleware(), null, 'foo');

        $params = ['foo' => 'bar'];

        $this->router
            ->expects($this->once())
            ->method('match')
            ->willReturn(RouteResult::fromRoute($route, $params));

        $request = $this->createRequestMock();
        $event   = $this->createMock(MvcEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $event
            ->expects($this->once())
            ->method('stopPropagation')
            ->with(true);

        $match = $this->listener->match($event);
        $this->assertInstanceOf(RouteMatch::class, $match);
        $this->assertEquals($params, $match->getParams());
        $this->assertEquals('foo', $match->getMatchedRouteName());
    }

    protected function setUp() : void
    {
        $this->router   = $this->createMock(RouterInterface::class);
        $this->listener = new RouteListener($this->router);
        parent::setUp();
    }
}
