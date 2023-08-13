<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Psr\Container\ContainerInterface;
use Workerman\Worker;

final class SupervisorWorker
{
    public function __construct(ContainerInterface $container, array $config, array $processConfig)
    {
        foreach ($processConfig as $serviceId => $serviceConfig) {
            if ($serviceConfig['processes'] !== null && $serviceConfig['processes'] <= 0) {
                continue;
            }

            $worker = new Worker();
            $worker->name = $serviceConfig['name'] ?? $serviceId;
            $worker->user = $config['user'] ?? '';
            $worker->group = $config['group'] ?? '';
            $worker->count = $serviceConfig['processes'] ?? 1;
            $worker->onWorkerStart = function(Worker $worker) use ($container, $serviceId, $serviceConfig) {
                $service = $container->get($serviceId);
                $method = $serviceConfig['method'] ?? '__invoke';
                $service->$method();
            };
        }
    }
}
