<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Luzrain\WorkermanBundle\ConfigLoader;

final class WorkermanExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container
            ->register('workerman.config_loader', ConfigLoader::class)
            ->setArgument('$cacheDir', $container->getParameter('kernel.cache_dir'))
            ->setArgument('$isDebug',$container->getParameter('kernel.debug'))
            ->addMethodCall('setConfig', [$config])
            ->addTag('kernel.cache_warmer')
        ;
    }
}
