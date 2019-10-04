<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouterTest\ExpressiveRouter;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\Dumper;
use Boesing\ZendRouterToExpressiveRouter\Middleware\DummyMiddleware;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Router\Route;

use function bin2hex;
use function file_get_contents;
use function random_bytes;

final class DumperTest extends TestCase
{
    /** @var Dumper */
    private $dumper;

    protected function setUp() : void
    {
        parent::setUp();
        $this->dumper = new Dumper();
    }

    public function testWillDumpExpectedDataToFile() : void
    {
        $path = bin2hex(random_bytes(3));
        $root = vfsStream::setup($path);

        $directory = $root->url();

        $route = new Route('/foo', new DummyMiddleware(), Route::HTTP_METHOD_ANY, 'foo');
        $route->setOptions(['defaults' => ['bar' => 'baz']]);

        $destination = $directory . '/' . 'test.php';
        $this->dumper->dump([$route], $destination);

        $data = file_get_contents($destination);
        $this->assertEquals(<<<EOT
<?php
declare(strict_types=1);

return array (
  0 => 
  array (
    'name' => 'foo',
    'path' => '/foo',
    'options' => 
    array (
      'defaults' => 
      array (
        'bar' => 'baz',
      ),
    ),
    'allowed_methods' => NULL,
  ),
);
EOT, $data);
    }
}
