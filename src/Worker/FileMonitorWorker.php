<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Luzrain\WorkermanBundle\ExtendedWorker as Worker;
use Luzrain\WorkermanBundle\Reboot\FileMonitorWatcherFactory;

final class FileMonitorWorker
{
    public const PROCESS_TITLE = 'FileMonitor';

    public function __construct(string|null $user, string|null $group, array $sourceDir, array $filePattern)
    {
        $worker = new Worker();
        $worker->name = sprintf('[%s]', self::PROCESS_TITLE);
        $worker->user = $user ?? '';
        $worker->group = $group ?? '';
        $worker->count = 1;
        $worker->reloadable = false;
        $worker->onWorkerStart = function (Worker $worker) use ($sourceDir, $filePattern) {
            $this->log('started');
            $fileMonitor = FileMonitorWatcherFactory::create($sourceDir, $filePattern, $this->log(...));
            $fileMonitor->start();
        };
    }

    private function log(string $message): void
    {
        Worker::log(sprintf('[%s] %s', self::PROCESS_TITLE, $message));
    }
}
