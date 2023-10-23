<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\DependencyInjection;

use Luzrain\WorkermanBundle\Reboot\StackRebootStrategy;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $processes = array_map(fn (array $a) => $a[0], $container->findTaggedServiceIds('workerman.process'));
        $jobs = array_map(fn (array $a) => $a[0], $container->findTaggedServiceIds('workerman.job'));
        $rebootStrategies = array_map(fn (array $a) => $a[0], $container->findTaggedServiceIds('workerman.reboot_strategy'));

        $container
            ->getDefinition('workerman.config_loader')
            ->addMethodCall('setProcessConfig', [$processes])
            ->addMethodCall('setSchedulerConfig', [$jobs])
        ;

        $container
            ->register('workerman.process_locator', ServiceLocator::class)
            ->addTag('container.service_locator')
            ->setArguments([$this->referenceMap($processes)])
            ->setPublic(true)
        ;

        $container
            ->register('workerman.scheduledjob_locator', ServiceLocator::class)
            ->addTag('container.service_locator')
            ->setArguments([$this->referenceMap($jobs)])
            ->setPublic(true)
        ;

        $container
            ->register('workerman.reboot_strategy', StackRebootStrategy::class)
            ->setArguments([$this->referenceMap($rebootStrategies)])
        ;
    }

    private function referenceMap(array $taggedServices): array
    {
        $result = [];
        foreach ($taggedServices as $id => $tags) {
            $result[$id] = new Reference($id);
        }
        return $result;
    }
}
