<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
final class DummyMiddleware implements MiddlewareInterface
{
    public static function __set_state()
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        return $handler->handle($request);
    }
}
