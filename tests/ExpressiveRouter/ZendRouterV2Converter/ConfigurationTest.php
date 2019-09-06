<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouterTest\ExpressiveRouter\ZendRouterV2Converter;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ZendRouterV2Converter\Configuration;
use PHPUnit\Framework\TestCase;

final class ConfigurationTest extends TestCase
{
    /**
     * @dataProvider blacklistedRouteNamesProvider
     *
     * @test
     */
    public function willReturnBlacklistedRouteNames(array $options, string $key) : void
    {
        $blacklisted   = $options[$key];
        $configuration = new Configuration($options);
        $this->assertEquals($blacklisted, $configuration->getBlacklistedRouteNames());
    }

    public function blacklistedRouteNamesProvider() : array
    {
        return [
            'camel_case'    => [
                ['blacklisted_route_names' => ['foo', 'bar', 'baz']],
                'blacklisted_route_names',
            ],
            'snakeCase'     => [
                ['blacklistedRouteNames' => ['baz', 'foo', 'bar']],
                'blacklistedRouteNames',
            ],
            'whatever_Case' => [
                ['blacklisted_RouteNames' => ['foo', 'baz', 'bar']],
                'blacklisted_RouteNames',
            ],
        ];
    }
}
