<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $processes = array_map(fn (array $a) => $a[0], $container->findTaggedServiceIds('workerman.process'));
        $jobs = array_map(fn (array $a) => $a[0], $container->findTaggedServiceIds('workerman.job'));

        $container
            ->register('workerman.process_locator', ServiceLocator::class)
            ->setArguments([$this->referenceMap($processes)])
            ->addTag('container.service_locator')
            ->setPublic(true)
        ;

        $container
            ->register('workerman.scheduledjob_locator', ServiceLocator::class)
            ->setArguments([$this->referenceMap($jobs)])
            ->addTag('container.service_locator')
            ->setPublic(true)
        ;

        $container
            ->getDefinition('workerman.config_loader')
            ->addMethodCall('setProcessConfig', [$processes])
            ->addMethodCall('setSchedulerConfig', [$jobs])
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
