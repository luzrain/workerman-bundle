<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\DependencyInjection;

use Luzrain\WorkermanBundle\Attribute\AsScheduledJob;
use Luzrain\WorkermanBundle\Attribute\AsProcess;
use Symfony\Component\DependencyInjection\ChildDefinition;
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
            ->setArgument('$isDebug', $container->getParameter('kernel.debug'))
            ->addMethodCall('setWorkermanConfig', [$config])
            ->addTag('kernel.cache_warmer')
        ;

        $container->registerAttributeForAutoconfiguration(AsProcess::class, $this->processConfig(...));
        $container->registerAttributeForAutoconfiguration(AsScheduledJob::class, $this->scheduledJobConfig(...));
    }

    private function processConfig(ChildDefinition $definition, AsProcess $attribute, \ReflectionClass $reflector): void
    {
        $definition->addTag('workerman.process', [
            'name' => $attribute->name ?? $reflector->getName(),
            'processes' => $attribute->processes,
            'method' => $attribute->method,
        ]);
    }

    private function scheduledJobConfig(ChildDefinition $definition, AsScheduledJob $attribute, \ReflectionClass $reflector): void
    {
        $definition->addTag('workerman.job', [
            'name' => $attribute->name ?? $reflector->getName(),
            'schedule' => $attribute->schedule,
            'method' => $attribute->method,
        ]);
    }
}
