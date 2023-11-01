<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Luzrain\WorkermanBundle\ExtendedWorker as Worker;
use Luzrain\WorkermanBundle\KernelFactory;
use Luzrain\WorkermanBundle\Supervisor\ProcessHandler;

final class SupervisorWorker
{
    private const PROCESS_TITLE = '[Process]';

    public function __construct(KernelFactory $kernelFactory, string|null $user, string|null $group, array $processConfig)
    {
        foreach ($processConfig as $serviceId => $serviceConfig) {
            if ($serviceConfig['processes'] !== null && $serviceConfig['processes'] <= 0) {
                continue;
            }

            $taskName = empty($serviceConfig['name']) ? $serviceId : $serviceConfig['name'];
            $worker = new Worker();
            $worker->name = self::PROCESS_TITLE;
            $worker->user = $user ?? '';
            $worker->group = $group ?? '';
            $worker->count = $serviceConfig['processes'] ?? 1;
            $worker->onWorkerStart = function (Worker $worker) use ($kernelFactory, $serviceId, $serviceConfig, $taskName) {
                $worker->doLog(sprintf('"%s" started', $taskName));
                $kernel = $kernelFactory->createKernel();
                $kernel->boot();
                /** @var ProcessHandler $handler */
                $handler = $kernel->getContainer()->get('workerman.process_handler');
                $method = empty($serviceConfig['method']) ? '__invoke' : $serviceConfig['method'];
                $handler("$serviceId::$method", $taskName);
                sleep(1);
                exit;
            };
        }
    }
}
