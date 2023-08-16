<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Luzrain\WorkermanBundle\KernelFactory;
use Luzrain\WorkermanBundle\SchedulerTrigger\TriggerFactory;
use Luzrain\WorkermanBundle\SchedulerTrigger\TriggerInterface;
use Psr\Container\ContainerInterface;
use Workerman\Timer;
use Workerman\Worker;

final class SchedulerWorker
{
    private const PROCESS_NAME = 'Scheduler';

    public function __construct(private KernelFactory $kernelFactory, array $config, array $cronJobConfig)
    {
        $worker = new Worker();
        $worker->name = sprintf('[%s]', self::PROCESS_NAME);
        $worker->user = $config['user'] ?? '';
        $worker->group = $config['group'] ?? '';
        $worker->count = 1;

        $worker->onWorkerStart = function(Worker $worker) use ($cronJobConfig) {
            Worker::log(sprintf('[%s] Start', self::PROCESS_NAME));

            \pcntl_signal(\SIGCHLD, \SIG_IGN);

            $kernel = $this->kernelFactory->createKernel();
            $kernel->boot();

            /** @var ContainerInterface $locator */
            $locator = $kernel->getContainer()->get('workerman.scheduledjob_locator');

            foreach ($cronJobConfig as $serviceId => $serviceConfig) {
                try {
                    $trigger = TriggerFactory::create($serviceConfig['schedule'], $serviceConfig['jitter'] ?? 0);
                } catch (\InvalidArgumentException) {
                    Worker::log(sprintf('[%s] Task "%s" skipped. Trigger "%s" is incorrect', self::PROCESS_NAME, $serviceConfig['name'], $serviceConfig['schedule']));
                    continue;
                }

                Worker::log(sprintf('[%s] Task "%s" scheduled. Trigger "%s"', self::PROCESS_NAME, $serviceConfig['name'], $trigger));
                $service = $locator->get($serviceId);
                $method = $serviceConfig['method'] ?? '__invoke';
                $this->scheduleCallback($trigger, $service->$method(...), $serviceConfig['name']);
            }
        };
    }

    private function scheduleCallback(TriggerInterface $trigger, \Closure $callback, string $name): void
    {
        $currentDate = new \DateTimeImmutable();
        $nextRunDate = $trigger->getNextRunDate($currentDate);
        if ($nextRunDate !== null) {
            $interval = $nextRunDate->getTimestamp() - $currentDate->getTimestamp();
            Timer::add($interval, $this->runCallback(...), [$trigger, $callback, $name], false);
        }
    }

    private function runCallback(TriggerInterface $trigger, \Closure $callback, string $name): void
    {
        if ($this->readTaskPid($callback) !== 0) {
            $this->scheduleCallback($trigger, $callback, $name);
            return;
        }

        $pid = \pcntl_fork();
        if ($pid === -1) {
            Worker::log(sprintf('[%s] Task "%s" start error!', self::PROCESS_NAME, $name));
        } else if ($pid > 0) {
            Worker::log(sprintf('[%s] Task "%s" started', self::PROCESS_NAME, $name));
            $this->scheduleCallback($trigger, $callback, $name);
        } else {
            Timer::delAll();
            \cli_set_process_title(\str_replace(self::PROCESS_NAME, $name, \cli_get_process_title()));
            $this->saveTaskPid($callback);
            try {
                $callback();
            } finally {
                $this->deleteTaskPid($callback);
                \posix_kill(\posix_getpid(), \SIGUSR1);
            }
        }
    }

    private function getTaskPidPath(\Closure $callback): string
    {
        return \sprintf('%s/workerman.task.%s.pid', \dirname(Worker::$pidFile), \spl_object_id($callback));
    }

    private function readTaskPid(\Closure $callback): int
    {
        try {
            return (int) \file_get_contents($this->getTaskPidPath($callback));
        } catch (\ErrorException) {
            return 0;
        }
    }

    private function saveTaskPid(\Closure $callback): void
    {
        if (\file_put_contents($this->getTaskPidPath($callback), posix_getpid()) === false) {
            throw new \Exception(sprintf('Can\'t save pid to %s', $this->getTaskPidPath($callback)));
        }
    }

    private function deleteTaskPid(\Closure $callback): void
    {
        @\unlink($this->getTaskPidPath($callback));
    }
}
