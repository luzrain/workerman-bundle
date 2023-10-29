<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Luzrain\WorkermanBundle\ExtendedWorker as Worker;
use Luzrain\WorkermanBundle\KernelFactory;
use Luzrain\WorkermanBundle\Scheduler\TaskHandler;
use Luzrain\WorkermanBundle\Scheduler\Trigger\TriggerFactory;
use Luzrain\WorkermanBundle\Scheduler\Trigger\TriggerInterface;
use Luzrain\WorkermanBundle\Utils;
use Workerman\Timer;

final class SchedulerWorker
{
    private const PROCESS_TITLE = 'Scheduler';

    private TaskHandler $handler;

    public function __construct(KernelFactory $kernelFactory, string|null $user, string|null $group, array $schedulerConfig)
    {
        $worker = new Worker();
        $worker->name = sprintf('[%s]', self::PROCESS_TITLE);
        $worker->user = $user ?? '';
        $worker->group = $group ?? '';
        $worker->count = 1;
        $worker->reloadable = true;
        $worker->onWorkerStart = function (Worker $worker) use ($kernelFactory, $schedulerConfig) {
            Worker::log(sprintf('[%s] started', self::PROCESS_TITLE));
            pcntl_signal(SIGCHLD, SIG_IGN);
            $kernel = $kernelFactory->createKernel();
            $kernel->boot();
            $this->handler = $kernel->getContainer()->get('workerman.task_handler');

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
                $method = $serviceConfig['method'] ?? '__invoke';
                $this->scheduleCallback($trigger, "$serviceId::$method", $jobName);
            }
        };
    }

    private function scheduleCallback(TriggerInterface $trigger, string $service, string $name): void
    {
        $currentDate = new \DateTimeImmutable();
        $nextRunDate = $trigger->getNextRunDate($currentDate);
        if ($nextRunDate !== null) {
            $interval = $nextRunDate->getTimestamp() - $currentDate->getTimestamp();
            Timer::add($interval, $this->runCallback(...), [$trigger, $service, $name], false);
        }
    }

    private function runCallback(TriggerInterface $trigger, string $service, string $name): void
    {
        $taskPid = Utils::getPid($this->getTaskPidPath($service));
        if ($taskPid !== 0) {
            $this->scheduleCallback($trigger, $service, $name);
            return;
        }

        $pid = \pcntl_fork();
        if ($pid === -1) {
            Worker::logWithLevel('ERROR', sprintf('[%s] Task "%s" call error!', self::PROCESS_TITLE, $name));
        } elseif ($pid > 0) {
            Worker::log(sprintf('[%s] Task "%s" called', self::PROCESS_TITLE, $name));
            $this->scheduleCallback($trigger, $service, $name);
        } else {
            // Child process start
            Timer::delAll();
            $title = str_replace(sprintf('[%s]', self::PROCESS_TITLE), sprintf('[%s] "%s"', self::PROCESS_TITLE, $name), cli_get_process_title());
            cli_set_process_title($title);
            $this->saveTaskPid($service);
            ($this->handler)($service, $name);
            unlink($this->getTaskPidPath($service));
            posix_kill(posix_getpid(), SIGKILL);
        }
    }

    private function getTaskPidPath(string $serviceId): string
    {
        return sprintf('%s/workerman.task.%s.pid', dirname(Worker::$pidFile), hash('xxh64', $serviceId));
    }

    private function saveTaskPid(string $serviceId): void
    {
        if (file_put_contents($this->getTaskPidPath($serviceId), posix_getpid()) === false) {
            Worker::logWithLevel('ERROR', sprintf('[%s] Can\'t save pid to %s', self::PROCESS_TITLE, $this->getTaskPidPath($serviceId)));
        }
    }
}
