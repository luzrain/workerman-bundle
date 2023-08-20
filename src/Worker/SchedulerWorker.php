<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Luzrain\WorkermanBundle\KernelFactory;
use Luzrain\WorkermanBundle\Scheduler\TriggerFactory;
use Luzrain\WorkermanBundle\Scheduler\TriggerInterface;
use Psr\Container\ContainerInterface;
use Workerman\Timer;
use Workerman\Worker;

final class SchedulerWorker
{
    private const PROCESS_TITLE = 'Scheduler';

    public static function run(KernelFactory $kernelFactory, array $config, array $cronJobConfig)
    {
        $worker = new Worker();
        $worker->name = sprintf('[%s]', self::PROCESS_TITLE);
        $worker->user = $config['user'] ?? '';
        $worker->group = $config['group'] ?? '';
        $worker->count = 1;

        $worker->onWorkerStart = function(Worker $worker) use ($kernelFactory, $cronJobConfig) {
            Worker::log(sprintf('[%s] started', self::PROCESS_TITLE));

            \pcntl_signal(\SIGCHLD, \SIG_IGN);
            $kernel = $kernelFactory->createKernel();
            $kernel->boot();

            /** @var ContainerInterface $locator */
            $locator = $kernel->getContainer()->get('workerman.scheduledjob_locator');

            foreach ($cronJobConfig as $serviceId => $serviceConfig) {
                try {
                    $trigger = TriggerFactory::create($serviceConfig['schedule'], $serviceConfig['jitter'] ?? 0);
                } catch (\InvalidArgumentException) {
                    Worker::log(sprintf('[%s] Task "%s" skipped. Trigger "%s" is incorrect', self::PROCESS_TITLE, $serviceConfig['name'], $serviceConfig['schedule']));
                    continue;
                }

                Worker::log(sprintf('[%s] Task "%s" scheduled. Trigger: "%s"', self::PROCESS_TITLE, $serviceConfig['name'], $trigger));
                $service = $locator->get($serviceId);
                $method = $serviceConfig['method'] ?? '__invoke';
                self::scheduleCallback($trigger, $service->$method(...), $serviceConfig['name']);
            }
        };
    }

    private static function scheduleCallback(TriggerInterface $trigger, \Closure $callback, string $name): void
    {
        $currentDate = new \DateTimeImmutable();
        $nextRunDate = $trigger->getNextRunDate($currentDate);
        if ($nextRunDate !== null) {
            $interval = $nextRunDate->getTimestamp() - $currentDate->getTimestamp();
            Timer::add($interval, self::runCallback(...), [$trigger, $callback, $name], false);
        }
    }

    private static function runCallback(TriggerInterface $trigger, \Closure $callback, string $name): void
    {
        if (self::readTaskPid($callback) !== 0) {
            self::scheduleCallback($trigger, $callback, $name);
            return;
        }

        $pid = \pcntl_fork();
        if ($pid === -1) {
            Worker::log(sprintf('[%s] Task "%s" call error!', self::PROCESS_TITLE, $name));
        } else if ($pid > 0) {
            Worker::log(sprintf('[%s] Task "%s" called', self::PROCESS_TITLE, $name));
            self::scheduleCallback($trigger, $callback, $name);
        } else {
            Timer::delAll();
            $title = \str_replace(sprintf('[%s]', self::PROCESS_TITLE), sprintf('[%s] "%s"', self::PROCESS_TITLE, $name), \cli_get_process_title());
            \cli_set_process_title($title);
            self::saveTaskPid($callback);
            try {
                $callback();
            } finally {
                self::deleteTaskPid($callback);
                \posix_kill(\posix_getpid(), \SIGUSR1);
            }
        }
    }

    private static function getTaskPidPath(\Closure $callback): string
    {
        return \sprintf('%s/workerman.task.%s.pid', \dirname(Worker::$pidFile), \spl_object_id($callback));
    }

    private static function readTaskPid(\Closure $callback): int
    {
        try {
            return (int) @\file_get_contents(self::getTaskPidPath($callback));
        } catch (\ErrorException) {
            return 0;
        }
    }

    private static function saveTaskPid(\Closure $callback): void
    {
        if (\file_put_contents(self::getTaskPidPath($callback), posix_getpid()) === false) {
            throw new \Exception(sprintf('Can\'t save pid to %s', self::getTaskPidPath($callback)));
        }
    }

    private static function deleteTaskPid(\Closure $callback): void
    {
        @\unlink(self::getTaskPidPath($callback));
    }
}
