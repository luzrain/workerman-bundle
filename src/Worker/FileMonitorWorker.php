<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Luzrain\WorkermanBundle\Reboot\FileMonitorWatcherFactory;
use Workerman\Worker;

final class FileMonitorWorker
{
    public const PROCESS_TITLE = 'FileMonitor';

    public static function run(array $sourceDir, array $filePattern)
    {
        $worker = new Worker();
        $worker->name = sprintf('[%s]', self::PROCESS_TITLE);
        $worker->user = $config['user'] ?? '';
        $worker->group = $config['group'] ?? '';
        $worker->count = 1;
        $worker->reloadable = false;
        $worker->onWorkerStart = function (Worker $worker) use ($sourceDir, $filePattern) {
            self::log('started');
            $fileMonitor = FileMonitorWatcherFactory::create($sourceDir, $filePattern, self::log(...));
            $fileMonitor->start();
        };
    }

    private static function log(string $message): void
    {
        Worker::log(sprintf('[%s] %s', self::PROCESS_TITLE, $message));
    }
}
