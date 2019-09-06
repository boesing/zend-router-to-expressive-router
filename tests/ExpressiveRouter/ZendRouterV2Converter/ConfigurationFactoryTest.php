<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouterTest\ExpressiveRouter\ZendRouterV2Converter;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ZendRouterV2Converter\Configuration;
use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ZendRouterV2Converter\ConfigurationFactory;
use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ZendRouterV2Converter\ConfigurationInterface;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function random_bytes;

final class ConfigurationFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function willReadProperConfigurationKey() : void
    {
        $routeNames = [bin2hex(random_bytes(12))];

        $config = [
            ConfigurationInterface::CONFIG_KEY => [
                'blacklisted_route_names' => $routeNames,
            ],
        ];

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($config);

        $factory       = new ConfigurationFactory();
        $configuration = $factory($container->reveal(), ConfigurationInterface::class);
        $this->assertInstanceOf(Configuration::class, $configuration);
        $this->assertEquals($routeNames, $configuration->getBlacklistedRouteNames());
    }
}
