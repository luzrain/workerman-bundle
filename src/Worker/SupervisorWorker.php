<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Luzrain\WorkermanBundle\KernelFactory;
use Luzrain\WorkermanBundle\ExtendedWorker as Worker;

final class SupervisorWorker
{
    private const PROCESS_TITLE = 'Process';

    public function __construct(KernelFactory $kernelFactory, string|null $user, string|null $group, array $processConfig)
    {
        foreach ($processConfig as $serviceId => $serviceConfig) {
            if ($serviceConfig['processes'] !== null && $serviceConfig['processes'] <= 0) {
                continue;
            }

            $worker = new Worker();
            $worker->name = sprintf('[%s] "%s"', self::PROCESS_TITLE, $serviceConfig['name'] ?? $serviceId);
            $worker->user = $user ?? '';
            $worker->group = $group ?? '';
            $worker->count = $serviceConfig['processes'] ?? 1;
            $worker->onWorkerStart = function (Worker $worker) use ($kernelFactory, $serviceId, $serviceConfig) {
                Worker::log(sprintf('[%s] "%s" started', self::PROCESS_TITLE, $serviceConfig['name'] ?? $serviceId));

                $kernel = $kernelFactory->createKernel();
                $kernel->boot();

                $service = $kernel->getContainer()->get('workerman.process_locator')->get($serviceId);
                $method = $serviceConfig['method'] ?? '__invoke';
                $service->$method();
            };
        }
    }
}
