<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Luzrain\WorkermanBundle\KernelFactory;
use Psr\Container\ContainerInterface;
use Workerman\Worker;

final class SupervisorWorker
{
    private const PROCESS_TITLE = 'Process';

    public function __construct(private KernelFactory $kernelFactory, array $config, array $processConfig)
    {
        foreach ($processConfig as $serviceId => $serviceConfig) {
            if ($serviceConfig['processes'] !== null && $serviceConfig['processes'] <= 0) {
                continue;
            }

            $worker = new Worker();
            $worker->name = sprintf('[%s] "%s"', self::PROCESS_TITLE, $serviceConfig['name'] ?? $serviceId);
            $worker->user = $config['user'] ?? '';
            $worker->group = $config['group'] ?? '';
            $worker->count = $serviceConfig['processes'] ?? 1;
            $worker->onWorkerStart = function(Worker $worker) use ($serviceId, $serviceConfig) {
                Worker::log(sprintf('[%s] "%s" started', self::PROCESS_TITLE, $serviceConfig['name'] ?? $serviceId));

                $kernel = $this->kernelFactory->createKernel();
                $kernel->boot();

                /** @var ContainerInterface $locator */
                $locator = $kernel->getContainer()->get('workerman.process_locator');

                $service = $locator->get($serviceId);
                $method = $serviceConfig['method'] ?? '__invoke';
                $service->$method();
            };
        }
    }
}
