<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\DependencyInjection;

use Luzrain\WorkermanBundle\Reboot\StackRebootStrategy;
use Luzrain\WorkermanBundle\Scheduler\ErrorListener;
use Luzrain\WorkermanBundle\Scheduler\TaskHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $processes = array_map(fn(array $a) => $a[0], $container->findTaggedServiceIds('workerman.process'));
        $tasks = array_map(fn(array $a) => $a[0], $container->findTaggedServiceIds('workerman.task'));
        $rebootStrategies = array_map(fn(array $a) => $a[0], $container->findTaggedServiceIds('workerman.reboot_strategy'));

        $container
            ->getDefinition('workerman.config_loader')
            ->addMethodCall('setProcessConfig', [$processes])
            ->addMethodCall('setSchedulerConfig', [$tasks])
        ;

        $container
            ->register('workerman.process_locator', ServiceLocator::class)
            ->addTag('container.service_locator')
            ->setArguments([$this->referenceMap($processes)])
            ->setPublic(true)
        ;

        $container
            ->register('workerman.task_locator', ServiceLocator::class)
            ->addTag('container.service_locator')
            ->setArguments([$this->referenceMap($tasks)])
            ->setPublic(true)
        ;

        $container
            ->register('workerman.reboot_strategy', StackRebootStrategy::class)
            ->setArguments([$this->referenceMap($rebootStrategies)])
        ;

        $container
            ->register('workerman.task_handler', TaskHandler::class)
            ->setPublic(true)
            ->setArguments([
                new Reference('workerman.task_locator'),
                new Reference(EventDispatcherInterface::class),
            ])
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
