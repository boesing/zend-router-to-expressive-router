<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToFastroute\ExpressiveRouter\ZendRouterV2Converter;

interface ConfigurationInterface
{
    public const CONFIG_KEY = 'zend_router_to_fastroute:converter';

    /**
     * @return string[]
     */
    public function getBlacklistedRouteNames() : array;
}
