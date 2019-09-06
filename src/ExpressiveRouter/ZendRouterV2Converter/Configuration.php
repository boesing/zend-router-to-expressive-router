<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ZendRouterV2Converter;

use Webmozart\Assert\Assert;
use Zend\Stdlib\AbstractOptions;

use function array_values;

final class Configuration extends AbstractOptions implements ConfigurationInterface
{
    /** @var array<int,string> */
    protected $blacklistedRouteNames = [];

    public function getBlacklistedRouteNames() : array
    {
        return $this->blacklistedRouteNames;
    }

    /**
     * @param string[] $names
     *
     * @return void
     */
    public function setBlacklistedRouteNames(array $names) : void
    {
        Assert::allString($names);
        $this->blacklistedRouteNames = array_values($names);
    }
}
