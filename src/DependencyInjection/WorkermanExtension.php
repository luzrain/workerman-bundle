<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\DependencyInjection;

use Luzrain\WorkermanBundle\Attribute\AsProcess;
use Luzrain\WorkermanBundle\Attribute\AsScheduledJob;
use Luzrain\WorkermanBundle\ConfigLoader;
use Luzrain\WorkermanBundle\Reboot\AlwaysRebootStrategy;
use Luzrain\WorkermanBundle\Reboot\ExceptionRebootStrategy;
use Luzrain\WorkermanBundle\Reboot\MaxJobsRebootStrategy;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class WorkermanExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container
            ->register('workerman.config_loader', ConfigLoader::class)
            ->setArguments([
                $container->getParameter('kernel.project_dir'),
                $container->getParameter('kernel.cache_dir'),
                $container->getParameter('kernel.debug'),
            ])
            ->addMethodCall('setWorkermanConfig', [$config])
            ->addTag('kernel.cache_warmer')
        ;

        if ($config['reload_strategy']['always']['active']) {
            $container
                ->register('workerman.always_reboot_strategy', AlwaysRebootStrategy::class)
                ->addTag('workerman.reboot_strategy')
            ;
        }

        if ($config['reload_strategy']['max_requests']['active']) {
            $container
                ->register('workerman.max_requests_reboot_strategy', MaxJobsRebootStrategy::class)
                ->addTag('workerman.reboot_strategy')
                ->setArguments([
                    $config['reload_strategy']['max_requests']['requests'],
                    $config['reload_strategy']['max_requests']['dispersion'],
                ])
            ;
        }

        if ($config['reload_strategy']['exception']['active']) {
            $container
                ->register('workerman.exception_reboot_strategy', ExceptionRebootStrategy::class)
                ->setArguments([$config['reload_strategy']['exception']['allowed_exceptions']])
                ->addTag('workerman.reboot_strategy')
                ->addTag('kernel.event_listener', [
                    'event' => 'kernel.exception',
                    'priority' => -100,
                    'method' => 'onException',
                ])
            ;
        }

        $container->registerAttributeForAutoconfiguration(AsProcess::class, $this->processConfig(...));
        $container->registerAttributeForAutoconfiguration(AsScheduledJob::class, $this->scheduledJobConfig(...));
    }

    private function processConfig(ChildDefinition $definition, AsProcess $attribute): void
    {
        $definition->addTag('workerman.process', [
            'name' => $attribute->name,
            'processes' => $attribute->processes,
            'method' => $attribute->method,
        ]);
    }

    private function scheduledJobConfig(ChildDefinition $definition, AsScheduledJob $attribute): void
    {
        $definition->addTag('workerman.job', [
            'name' => $attribute->name,
            'schedule' => $attribute->schedule,
            'method' => $attribute->method,
            'jitter' => $attribute->jitter,
        ]);
    }
}
