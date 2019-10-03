<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouterTest\ModuleManager;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ConverterInterface;
use Boesing\ZendRouterToExpressiveRouter\ModuleManager\ConfigListener;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Zend\ModuleManager\Listener\AbstractListener;
use Zend\ModuleManager\Listener\ConfigMergerInterface;
use Zend\ModuleManager\Listener\ListenerOptions;
use Zend\ModuleManager\ModuleEvent;

use function bin2hex;
use function random_bytes;
use function sprintf;

final class ConfigListenerTest extends TestCase
{
    /** @var ConverterInterface|ObjectProphecy */
    private $converter;

    /**
     * @test
     */
    public function willBreakOnMissingConfigMerger() : void
    {
        $event = $this->createMock(ModuleEvent::class);
        $event
            ->expects($this->once())
            ->method('getConfigListener')
            ->willReturn(null);

        $listener = new ConfigListener($this->converter->reveal());
        $listener->convertZendRouterRoutesToExpressiveRoutes($event);
    }

    /**
     * @test
     */
    public function willBreakIfConversionAlreadyStoredInCachedConfiguration() : void
    {
        $listener = $this
            ->prophesize(AbstractListener::class)
            ->willImplement(ConfigMergerInterface::class);

        $listenerOptions = $this->createMock(ListenerOptions::class);
        $listenerOptions
            ->expects($this->once())
            ->method('getConfigCacheEnabled')
            ->willReturn(true);

        $listenerOptions
            ->expects($this->once())
            ->method('getConfigCacheFile')
            ->willReturn($this->createVirtualCacheFile()->url());

        $listener
            ->getOptions()
            ->willReturn($listenerOptions);

        $event = $this->createMock(ModuleEvent::class);
        $event
            ->expects($this->once())
            ->method('getConfigListener')
            ->willReturn($listener->reveal());

        $listener = new ConfigListener($this->converter->reveal());
        $listener->convertZendRouterRoutesToExpressiveRoutes($event);
    }

    private function createVirtualCacheFile() : vfsStreamFile
    {
        $path = bin2hex(random_bytes(3));
        $root = vfsStream::setup($path);

        return vfsStream::newFile(sprintf('%s.php', bin2hex(random_bytes(4))))->at($root);
    }

    /**
     * @test
     */
    public function willConvertRoutesAndApplyToMergedConfiguration() : void
    {
        /** @var ConfigMergerInterface|AbstractListener $listener */
        $listener = $this
            ->prophesize(AbstractListener::class)
            ->willImplement(ConfigMergerInterface::class);

        $listenerOptions = $this->createMock(ListenerOptions::class);
        $listenerOptions
            ->expects($this->once())
            ->method('getConfigCacheEnabled')
            ->willReturn(false);

        $listenerOptions
            ->expects($this->never())
            ->method('getConfigCacheFile');

        $listener
            ->getOptions()
            ->willReturn($listenerOptions);

        $config = ['foo' => 'bar', 'router' => ['routes' => ['foo' => 'bar']]];
        $listener
            ->getMergedConfig(false)
            ->willReturn($config);

        $converted = ['bar' => 'foo'];
        $merged    = $config + ['routes' => $converted];

        $listener
            ->setMergedConfig($merged)
            ->willReturn();

        $this
            ->converter
            ->convert(['foo' => 'bar'])
            ->willReturn($converted);

        $event = $this->createMock(ModuleEvent::class);
        $event
            ->expects($this->once())
            ->method('getConfigListener')
            ->willReturn($listener->reveal());

        $listener = new ConfigListener($this->converter->reveal());
        $listener->convertZendRouterRoutesToExpressiveRoutes($event);
    }

    /**
     * @test
     */
    public function willConvertConfigurationIfCacheDoesNotExist() : void
    {
        /** @var ObjectProphecy|ConfigMergerInterface|AbstractListener $listener */
        $listener = $this
            ->prophesize(AbstractListener::class)
            ->willImplement(ConfigMergerInterface::class);

        $listenerOptions = $this->createMock(ListenerOptions::class);
        $listenerOptions
            ->expects($this->once())
            ->method('getConfigCacheEnabled')
            ->willReturn(true);

        $listenerOptions
            ->expects($this->once())
            ->method('getConfigCacheFile')
            ->willReturn(bin2hex(random_bytes(10)));

        $listener
            ->getOptions()
            ->willReturn($listenerOptions);

        $config = ['foo' => 'bar', 'router' => ['routes' => ['foo' => 'bar']]];
        $listener
            ->getMergedConfig(false)
            ->willReturn($config);

        $converted = ['bar' => 'foo'];
        $merged    = $config + ['routes' => $converted];

        $listener
            ->setMergedConfig($merged)
            ->willReturn();

        $this
            ->converter
            ->convert(['foo' => 'bar'])
            ->willReturn($converted);

        $event = $this->createMock(ModuleEvent::class);
        $event
            ->expects($this->once())
            ->method('getConfigListener')
            ->willReturn($listener->reveal());

        $listener = new ConfigListener($this->converter->reveal());
        $listener->convertZendRouterRoutesToExpressiveRoutes($event);
    }

    /**
     * @test
     */
    public function canHandleNonAbstractListener() : void
    {
        /** @var ObjectProphecy|ConfigMergerInterface|AbstractListener $listener */
        $listener = $this
            ->prophesize(ConfigMergerInterface::class);

        $config = ['foo' => 'bar', 'router' => ['routes' => ['foo' => 'bar']]];
        $listener
            ->getMergedConfig(false)
            ->willReturn($config);

        $converted = ['bar' => 'foo'];
        $merged    = $config + ['routes' => $converted];

        $listener
            ->setMergedConfig($merged)
            ->willReturn();

        $this
            ->converter
            ->convert(['foo' => 'bar'])
            ->willReturn($converted);

        $event = $this->createMock(ModuleEvent::class);
        $event
            ->expects($this->once())
            ->method('getConfigListener')
            ->willReturn($listener->reveal());

        $listener = new ConfigListener($this->converter->reveal());
        $listener->convertZendRouterRoutesToExpressiveRoutes($event);
    }

    protected function setUp() : void
    {
        parent::setUp();

        $this->converter = $this->prophesize(ConverterInterface::class);
    }
}
