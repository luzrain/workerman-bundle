<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Reboot;

final class FileMonitorWatcherFactory
{
    public static function create(array $sourceDir, array $filePattern, \Closure $logger): FileMonitorWatcher
    {
        return \extension_loaded('inotify')
            ? new InotifyMonitorWatcher($sourceDir, $filePattern, $logger)
            : new PollingMonitorWatcher($sourceDir, $filePattern, $logger)
        ;
    }
}
