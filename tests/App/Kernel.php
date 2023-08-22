<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Test\App;

use Luzrain\WorkermanBundle\WorkermanBundle;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new WorkermanBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function(ContainerBuilder $container) use ($loader) {
            $container->loadFromExtension('framework', [
                'test' => true,
                'router' => [
                    'resource' => 'kernel::loadRoutes',
                    'type' => 'service',
                ],
            ]);

            $container->loadFromExtension('workerman', [
                'webserver' => [
                    'listen' => 'http://127.0.0.1:8888',
                    'processes' => 1,
                    'relod_strategy' => ['exception'],
                ],
            ]);

            $container->register('kernel', self::class)
                ->addTag('controller.service_arguments')
                ->addTag('routing.route_loader')
            ;

            $container->autowire(Controller::class)->setAutoconfigured(true);
            $container->autowire(ScheduledJob::class)->setAutoconfigured(true);
            $container->autowire(Process::class)->setAutoconfigured(true);

            $container->register('nyholm.psr7.psr17_factory', Psr17Factory::class);
            $container->setAlias(ServerRequestFactoryInterface::class, 'nyholm.psr7.psr17_factory');
            $container->setAlias(StreamFactoryInterface::class, 'nyholm.psr7.psr17_factory');
            $container->setAlias(UploadedFileFactoryInterface::class, 'nyholm.psr7.psr17_factory');
            $container->setAlias(ResponseFactoryInterface::class, 'nyholm.psr7.psr17_factory');
        });
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('*.php', 'annotation');
    }
}
