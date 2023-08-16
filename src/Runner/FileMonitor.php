<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Runner;

use Luzrain\WorkermanBundle\FileMonitor\InotifyMonitor;
use Workerman\Worker;

final class FileMonitor
{
    private const PROCESS_TITLE = 'FileMonitor';

    public static function run(array $config)
    {
        $worker = new Worker();
        $worker->name = sprintf('[%s]', self::PROCESS_TITLE);
        $worker->user = $config['user'] ?? '';
        $worker->group = $config['group'] ?? '';
        $worker->count = 1;
        $worker->reloadable = false;
        $worker->onWorkerStart = function (Worker $worker) {
            Worker::log(sprintf('[%s] started', self::PROCESS_TITLE));
            new InotifyMonitor();
        };
    }
}
