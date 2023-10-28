<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Luzrain\WorkermanBundle\KernelFactory;
use Luzrain\WorkermanBundle\Scheduler\TriggerFactory;
use Luzrain\WorkermanBundle\Scheduler\TriggerInterface;
use Psr\Container\ContainerInterface;
use Workerman\Timer;
use Luzrain\WorkermanBundle\ExtendedWorker as Worker;

final class SchedulerWorker
{
    private const PROCESS_TITLE = 'Scheduler';

    public function __construct(KernelFactory $kernelFactory, string|null $user, string|null $group, array $schedulerConfig)
    {
        $worker = new Worker();
        $worker->name = sprintf('[%s]', self::PROCESS_TITLE);
        $worker->user = $user ?? '';
        $worker->group = $group ?? '';
        $worker->count = 1;
        $worker->reloadable = false;
        $worker->onWorkerStart = function (Worker $worker) use ($kernelFactory, $schedulerConfig) {
            Worker::log(sprintf('[%s] started', self::PROCESS_TITLE));

            \pcntl_signal(\SIGCHLD, \SIG_IGN);
            $kernel = $kernelFactory->createKernel();
            $kernel->boot();

            /** @var ContainerInterface $locator */
            $locator = $kernel->getContainer()->get('workerman.scheduledjob_locator');

            foreach ($schedulerConfig as $serviceId => $serviceConfig) {
                $jobName = empty($serviceConfig['name']) ? $serviceId : $serviceConfig['name'];

                if (empty($serviceConfig['schedule'])) {
                    Worker::logWithLevel('WARNING', sprintf('[%s] Task "%s" skipped. Trigger has not been set', self::PROCESS_TITLE, $jobName));
                    continue;
                }

                try {
                    $trigger = TriggerFactory::create($serviceConfig['schedule'], $serviceConfig['jitter'] ?? 0);
                } catch (\InvalidArgumentException) {
                    Worker::logWithLevel('WARNING', sprintf('[%s] Task "%s" skipped. Trigger "%s" is incorrect', self::PROCESS_TITLE, $jobName, $serviceConfig['schedule']));
                    continue;
                }

                Worker::log(sprintf('[%s] Task "%s" scheduled. Trigger: "%s"', self::PROCESS_TITLE, $jobName, $trigger));
                $service = $locator->get($serviceId);
                $method = $serviceConfig['method'] ?? '__invoke';
                $this->scheduleCallback($trigger, $service->$method(...), $jobName);
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
            Worker::logWithLevel('EMERGENCY', sprintf('[%s] Task "%s" call error!', self::PROCESS_TITLE, $name));
        } elseif ($pid > 0) {
            Worker::log(sprintf('[%s] Task "%s" called', self::PROCESS_TITLE, $name));
            $this->scheduleCallback($trigger, $callback, $name);
        } else {
            // Child process start
            Timer::delAll();
            $title = \str_replace(sprintf('[%s]', self::PROCESS_TITLE), sprintf('[%s] "%s"', self::PROCESS_TITLE, $name), \cli_get_process_title());
            \cli_set_process_title($title);
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
            return (int) @\file_get_contents($this->getTaskPidPath($callback));
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
