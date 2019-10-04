<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter;

use Webmozart\Assert\Assert;
use Zend\Expressive\Router\Route;

use function dirname;
use function Safe\file_put_contents;
use function Safe\sprintf;
use function var_export;

final class Dumper
{
    private const TEMPLATE = <<<EOT
<?php
declare(strict_types=1);

return %s;
EOT;

    /**
     * @param Route[] $routes
     */
    public function dump(array $routes, string $path) : void
    {
        Assert::writable(dirname($path));

        $data = $this->toArray($routes);
        file_put_contents($path, sprintf(self::TEMPLATE, var_export($data, true)));
    }

    /**
     * @param Route[] $routes
     * @return array<int,array<string,string|array|null>>
     */
    private function toArray(array $routes) : array
    {
        Assert::allIsInstanceOf($routes, Route::class);
        $data = [];
        foreach ($routes as $route) {
            $data[] = [
                'name'            => $route->getName(),
                'path'            => $route->getPath(),
                'options'         => $route->getOptions(),
                'allowed_methods' => $route->getAllowedMethods(),
            ];
        }

        return $data;
    }
}
