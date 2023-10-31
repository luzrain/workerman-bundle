<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\DependencyInjection;

use Luzrain\WorkermanBundle\Attribute\AsProcess;
use Luzrain\WorkermanBundle\Attribute\AsTask;
use Luzrain\WorkermanBundle\Command\AboutCommand;
use Luzrain\WorkermanBundle\Command\ReloadCommand;
use Luzrain\WorkermanBundle\Command\StartCommand;
use Luzrain\WorkermanBundle\Command\StatusCommand;
use Luzrain\WorkermanBundle\Command\StopCommand;
use Luzrain\WorkermanBundle\ConfigLoader;
use Luzrain\WorkermanBundle\Http\WorkermanHttpMessageFactory;
use Luzrain\WorkermanBundle\KernelRunner;
use Luzrain\WorkermanBundle\Reboot\Strategy\AlwaysRebootStrategy;
use Luzrain\WorkermanBundle\Reboot\Strategy\ExceptionRebootStrategy;
use Luzrain\WorkermanBundle\Reboot\Strategy\MaxJobsRebootStrategy;
use Luzrain\WorkermanBundle\Scheduler\TaskErrorListener;
use Luzrain\WorkermanBundle\Supervisor\ProcessErrorListener;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\KernelInterface;

final class WorkermanExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container
            ->setParameter('workerman.response_chunk_size', $config['response_chunk_size'])
        ;

        $container
            ->register('workerman.config_loader', ConfigLoader::class)
            ->addMethodCall('setWorkermanConfig', [$config])
            ->addTag('kernel.cache_warmer')
            ->setArguments([
                $container->getParameter('kernel.project_dir'),
                $container->getParameter('kernel.cache_dir'),
                $container->getParameter('kernel.debug'),
            ])
        ;

        $container
            ->register('workerman.symfony_http_message_factory', PsrHttpFactory::class)
            ->setArguments([
                new Reference(ServerRequestFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
                new Reference(UploadedFileFactoryInterface::class),
                new Reference(ResponseFactoryInterface::class),
            ])
        ;

        $container
            ->register('workerman.http_foundation_factory', HttpFoundationFactory::class)
        ;

        $container
            ->register('workerman.workerman_http_message_factory', WorkermanHttpMessageFactory::class)
            ->setArguments([
                new Reference(ServerRequestFactoryInterface::class),
                new Reference(StreamFactoryInterface::class),
                new Reference(UploadedFileFactoryInterface::class),
            ])
        ;

        $container
            ->register('workerman.task_error_listener', TaskErrorListener::class)
            ->addTag('kernel.event_subscriber')
            ->addTag('monolog.logger', ['channel' => 'task'])
            ->setArguments([
                new Reference('logger'),
            ])
        ;

        $container
            ->register('workerman.process_error_listener', ProcessErrorListener::class)
            ->addTag('kernel.event_subscriber')
            ->addTag('monolog.logger', ['channel' => 'process'])
            ->setArguments([
                new Reference('logger'),
            ])
        ;

        $container
            ->register('workerman.kernel_runner', KernelRunner::class)
            ->setArguments([new Reference(KernelInterface::class)])
        ;

        $container
            ->register('workerman.command.start', StartCommand::class)
            ->addTag('console.command')
            ->addTag('monolog.logger', ['channel' => 'workerman'])
            ->setArguments([
                new Reference('workerman.kernel_runner'),
                new Reference('logger'),
                $config['pid_file'],
            ])
        ;

        $container
            ->register('workerman.command.stop', StopCommand::class)
            ->addTag('console.command')
            ->setArguments([
                new Reference('workerman.kernel_runner'),
                $config['pid_file'],
            ])
        ;

        $container
            ->register('workerman.command.status', StatusCommand::class)
            ->addTag('console.command')
            ->setArguments([
                new Reference('workerman.kernel_runner'),
                $config['pid_file'],
            ])
        ;

        $container
            ->register('workerman.command.restart', ReloadCommand::class)
            ->addTag('console.command')
            ->setArguments([
                $config['pid_file'],
            ])
        ;

        $container
            ->register('workerman.command.about', AboutCommand::class)
            ->addTag('console.command')
        ;

        $container->registerAttributeForAutoconfiguration(AsProcess::class, $this->processConfig(...));
        $container->registerAttributeForAutoconfiguration(AsTask::class, $this->taskConfig(...));

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
                ->addTag('workerman.reboot_strategy')
                ->addTag('kernel.event_listener', [
                    'event' => 'kernel.exception',
                    'priority' => -100,
                    'method' => 'onException',
                ])
                ->setArguments([
                    $config['reload_strategy']['exception']['allowed_exceptions'],
                ])
            ;
        }
    }

    private function processConfig(ChildDefinition $definition, AsProcess $attribute): void
    {
        $definition->addTag('workerman.process', [
            'name' => $attribute->name,
            'processes' => $attribute->processes,
            'method' => $attribute->method,
        ]);
    }

    private function taskConfig(ChildDefinition $definition, AsTask $attribute): void
    {
        $definition->addTag('workerman.task', [
            'name' => $attribute->name,
            'schedule' => $attribute->schedule,
            'method' => $attribute->method,
            'jitter' => $attribute->jitter,
        ]);
    }
}
