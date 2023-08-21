<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('workerman');

        $treeBuilder
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('user')
                    ->info('Unix user of processes. Default: current user')
                    ->defaultNull()
                    ->end()
                ->scalarNode('group')
                    ->info('Unix group of processes. Default: current group')
                    ->defaultNull()
                    ->end()
                ->integerNode('stop_timeout')
                    ->info('Max seconds of child process work before force kill')
                    ->defaultValue(2)
                    ->end()
                ->scalarNode('pid_file')
                    ->info('File to store master process PID')
                    ->cannotBeEmpty()
                    ->defaultValue('%kernel.project_dir%/var/run/workerman.pid')
                    ->end()
                ->scalarNode('log_file')
                    ->info('Log file')
                    ->cannotBeEmpty()
                    ->defaultValue('%kernel.project_dir%/var/log/workerman.log')
                    ->end()
                ->scalarNode('stdout_file')
                    ->info('File to write all output (echo var_dump, etc.) to when server is running as daemon')
                    ->cannotBeEmpty()
                    ->defaultValue('%kernel.project_dir%/var/log/workerman.stdout.log')
                    ->end()
                ->integerNode('max_package_size')
                    ->info('Max package size can be received')
                    ->defaultValue(10 * 1024 * 1024)
                    ->end()
                ->arrayNode('webserver')
                    ->info('Webserver configugation')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('name')
                            ->info('Webserver process name')
                            ->defaultValue('Symfony Workerman Server')
                            ->end()
                        ->scalarNode('listen')->defaultValue('http://0.0.0.0:80')
                            ->info('Listen address (http and https supported)')
                            ->validate()
                                ->ifTrue($this->listenValidate(...))
                                ->thenInvalid('The supported protocols is http:// and https://')
                                ->end()
                            ->end()
                        ->scalarNode('local_cert')
                            ->info('Path to local certificate file on filesystem')
                            ->defaultNull()
                            ->end()
                        ->scalarNode('local_pk')
                            ->info('Path to local private key file on filesystem')
                            ->defaultNull()
                            ->end()
                        ->integerNode('processes')
                            ->info('Number of webserver worker processes. Default: number of CPU cores * 2')
                            ->defaultNull()
                            ->end()
                        ->arrayNode('relod_strategy')
                            ->info('Array of reload strategies. Available strategies: ' . implode(', ', $this->getAvailableReloadStrategies()))
                            ->prototype('scalar')->end()
                            ->defaultValue(['exception', 'file_monitor'])
                            ->validate()
                                ->always()
                                ->then($this->relodStrategyValidate(...))
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->arrayNode('relod_strategy')
                    ->info('Reload strategies configuration')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('exception')
                            ->info('If an exception is thrown during the request handling, the worker is rebooted')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('allowed_exceptions')
                                    ->prototype('scalar')->end()
                                    ->defaultValue([
                                        'Symfony\Component\HttpKernel\Exception\HttpExceptionInterface',
                                        'Symfony\Component\Serializer\Exception\ExceptionInterface',
                                    ])
                                    ->end()
                                ->end()
                            ->end()
                        ->arrayNode('max_requests')
                            ->info('The worker is rebooted on every N request to prevent memory leaks')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('requests')
                                    ->info('Maximum number of request after that worker will be reloaded')
                                    ->defaultValue(1000)
                                    ->end()
                                ->integerNode('dispersion')
                                    ->info('Prevent simultaneous reboot all workers (1000 requests and 20% dispersion will rebooted between 800 and 1000)')
                                    ->defaultValue(20)
                                    ->end()
                                ->end()
                            ->end()
                        ->arrayNode('file_monitor')
                            ->info('All workers is rebooted each time that you change files')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('source_dir')
                                    ->prototype('scalar')->end()
                                    ->defaultValue([
                                        '%kernel.project_dir%/src',
                                        '%kernel.project_dir%/config',
                                    ])
                                    ->end()
                                ->arrayNode('file_pattern')
                                    ->prototype('scalar')->end()
                                    ->defaultValue([
                                        '*.php',
                                        '*.yaml',
                                    ])
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }

    private function listenValidate(string $listen): bool
    {
        return !(str_starts_with($listen, 'http://') || str_starts_with($listen, 'https://'));
    }

    private function relodStrategyValidate(array $array): array
    {
        $unsupportedStrategies = [];

        foreach ($array as $strategy) {
            if (!in_array($strategy, $this->getAvailableReloadStrategies(), true)) {
                $unsupportedStrategies[] = $strategy;
            }
        }

        if (!empty($unsupportedStrategies)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Strategy %s is not available. Available strategies: %s',
                    implode(', ', $unsupportedStrategies),
                    implode(', ', $this->getAvailableReloadStrategies()),
                ),
            );
        }

        return $array;
    }

    private function getAvailableReloadStrategies(): array
    {
        return ['always', 'exception', 'max_requests', 'file_monitor'];
    }
}
