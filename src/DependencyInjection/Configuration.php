<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Luzrain\WorkermanBundle\Utils;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('workerman');

        $treeBuilder
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('user')->defaultNull()->end()
                ->scalarNode('group')->defaultNull()->end()
                ->integerNode('stop_timeout')->defaultValue(2)->end()
                ->scalarNode('pid_file')->cannotBeEmpty()->defaultValue('%kernel.project_dir%/var/run/workerman.pid')->end()
                ->scalarNode('log_file')->cannotBeEmpty()->defaultValue('%kernel.project_dir%/var/log/workerman.log')->end()
                ->scalarNode('stdout_file')->cannotBeEmpty()->defaultValue('%kernel.project_dir%/var/log/workerman.stdout.log')->end()
                ->integerNode('max_package_size')->defaultValue(10 * 1024 * 1024)->end()
                ->integerNode('cron_processes')->defaultValue(1)->end()
                ->arrayNode('webserver')
                    ->children()
                        ->scalarNode('name')->defaultValue('Symfony Workerman Server')->end()
                        ->scalarNode('listen')->defaultValue('http://0.0.0.0:80')
                            ->validate()
                                ->ifTrue(fn (string $listen) => !str_starts_with($listen, 'http://') && !str_starts_with($listen, 'https://'))
                                ->thenInvalid('The supported protocols is http:// and https://')
                            ->end()
                        ->end()
                        ->scalarNode('local_cert')->defaultNull()->end()
                        ->scalarNode('local_pk')->defaultNull()->end()
                        ->integerNode('processes')->defaultNull()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
