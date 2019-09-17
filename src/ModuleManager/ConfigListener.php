<?php

declare(strict_types=1);

namespace Boesing\ZendRouterToExpressiveRouter\ModuleManager;

use Boesing\ZendRouterToExpressiveRouter\ExpressiveRouter\ConverterInterface;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Expressive\Router\RouterInterface;
use Zend\ModuleManager\Listener\AbstractListener;
use Zend\ModuleManager\Listener\ConfigMergerInterface;
use Zend\ModuleManager\Listener\ListenerOptions;
use Zend\ModuleManager\ModuleEvent;

use function file_exists;

final class ConfigListener extends AbstractListenerAggregate
{
    /** @var ConverterInterface */
    private $converter;

    public function __construct(ConverterInterface $converter)
    {
        $this->converter = $converter;
    }

    /**
     * @inheritDoc
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(ModuleEvent::EVENT_MERGE_CONFIG, [
            $this,
            'convertZendRouterRoutesToExpressiveRoutes',
        ], -100000);
    }

    public function convertZendRouterRoutesToExpressiveRoutes(ModuleEvent $event) : void
    {
        $configListener = $event->getConfigListener();
        if (! $configListener instanceof ConfigMergerInterface) {
            return;
        }

        $options = $this->getConfigListenerOptions($configListener);
        if ($this->conversionAlreadyDone($options)) {
            return;
        }

        $config                         = $configListener->getMergedConfig(false);
        $routes                         = $config['router']['routes'] ?? [];
        $convertedRoutes                = $this->converter->convert($routes);
        $config['routes'] = $convertedRoutes;
        $configListener->setMergedConfig($config);
    }

    private function getConfigListenerOptions(ConfigMergerInterface $configListener) : ListenerOptions
    {
        if ($configListener instanceof AbstractListener) {
            return $configListener->getOptions();
        }

        return new ListenerOptions([]);
    }

    /**
     * If the configuration is cached and the file exists, we can assume that we already made our conversion.
     */
    private function conversionAlreadyDone(ListenerOptions $options) : bool
    {
        if (! $options->getConfigCacheEnabled()) {
            return false;
        }

        if (! file_exists($options->getConfigCacheFile())) {
            return false;
        }

        return true;
    }
}
