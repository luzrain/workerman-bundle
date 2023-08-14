<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Luzrain\WorkermanBundle\SchedulerTrigger\DateTimeTrigger;
use Luzrain\WorkermanBundle\SchedulerTrigger\PeriodicalTrigger;
use Luzrain\WorkermanBundle\SchedulerTrigger\TriggerFactory;
use Luzrain\WorkermanBundle\SchedulerTrigger\TriggerInterface;
use Psr\Container\ContainerInterface;
use Workerman\Timer;
use Workerman\Worker;

final class SchedulerWorker
{
    private const PROCESS_NAME = 'Scheduler';

    public function __construct(ContainerInterface $container, array $config, array $cronJobConfig)
    {
        $worker = new Worker();
        $worker->name = self::PROCESS_NAME;
        $worker->user = $config['user'] ?? '';
        $worker->group = $config['group'] ?? '';
        $worker->count = 1;
        $worker->onWorkerStart = function(Worker $worker) use ($container, $cronJobConfig) {
            \pcntl_signal(\SIGCHLD, \SIG_IGN);
            foreach ($cronJobConfig as $serviceId => $serviceConfig) {
                $trigger = TriggerFactory::create($serviceConfig['schedule'], $serviceConfig['jitter'] ?? 0);
                $service = $container->get($serviceId);
                $method = $serviceConfig['method'] ?? '__invoke';
                $this->scheduleCallback($trigger, $service->$method(...), $serviceConfig['name']);
            }
        };
    }

    private function scheduleCallback(TriggerInterface $trigger, \Closure $callback, string $name): void
    {
        $currentDate = new \DateTimeImmutable();
        $nextRunDate = $trigger->getNextRunDate($currentDate);
        $interval = $nextRunDate->getTimestamp() - $currentDate->getTimestamp();
        Timer::add($interval, $this->runCallback(...), [$trigger, $callback, $name], false);
    }

    private function runCallback(TriggerInterface $trigger, \Closure $callback, string $name): void
    {
        if ($this->readTaskPid($callback) !== 0) {
            $this->scheduleCallback($trigger, $callback, $name);
            return;
        }

        $pid = \pcntl_fork();
        if ($pid === -1) {
            echo(\sprintf("Cron task \"%s\" start error!\n", $name));
        } else if ($pid > 0) {
            echo(\sprintf("Cron task \"%s\" starts...\n", $name));
            $this->scheduleCallback($trigger, $callback, $name);
        } else {
            Timer::delAll();
            \cli_set_process_title(\str_replace(self::PROCESS_NAME, $name, \cli_get_process_title()));
            $this->saveTaskPid($callback);
            try {
                $callback();
            } finally {
                $this->deleteTaskPid($callback);
            }
            \posix_kill(\posix_getpid(), \SIGUSR1);
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
