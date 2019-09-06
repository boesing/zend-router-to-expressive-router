<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter;

use Zend\Expressive\Router\Route;

interface ConverterInterface
{
    public const HOSTNAME = 'hostname';

    /**
     * @param array $routes
     *
     * @return Route[]
     */
    public function convert(array $routes) : array;
}
