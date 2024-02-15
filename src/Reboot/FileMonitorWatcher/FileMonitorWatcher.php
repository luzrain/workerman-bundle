<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Reboot\FileMonitorWatcher;

use Luzrain\WorkermanBundle\Utils;
use Workerman\Worker;

abstract class FileMonitorWatcher
{
    protected readonly Worker $worker;
    protected readonly array $sourceDir;
    private array $filePattern;

    public static function create(Worker $worker, array $sourceDir, array $filePattern): self
    {
        return \extension_loaded('inotify')
            ? new InotifyMonitorWatcher($worker, $sourceDir, $filePattern)
            : new PollingMonitorWatcher($worker, $sourceDir, $filePattern)
        ;
    }

    protected function __construct(Worker $worker, array $sourceDir, array $filePattern)
    {
        $this->worker = $worker;
        $this->sourceDir = array_filter($sourceDir, is_dir(...));
        $this->filePattern = $filePattern;
    }

    abstract public function start(): void;

    final protected function checkPattern(string $filename): bool
    {
        foreach ($this->filePattern as $pattern) {
            if (\fnmatch($pattern, $filename)) {
                return true;
            }
        }

        return false;
    }

    final protected function reboot(): void
    {
        Utils::reboot(rebootAllWorkers: true);
    }
}
