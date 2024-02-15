<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Reboot\FileMonitorWatcher;

use Workerman\Timer;

final class PollingMonitorWatcher extends FileMonitorWatcher
{
    private const POLLING_INTERVAL = 1;
    private const TO_MANY_FILES_WARNING_LIMIT = 1000;

    private int $lastMTime;
    private bool $toManyFiles = false;

    public function start(): void
    {
        $this->lastMTime = time();
        Timer::add(self::POLLING_INTERVAL, $this->checkFileSystemChanges(...));
        $this->worker->doLog('Polling file monitoring can be inefficient if the project has many files. Install the php-inotify extension to increase performance.', 'NOTICE');
    }

    private function checkFileSystemChanges(): void
    {
        $filesCout = 0;

        foreach ($this->sourceDir as $dir) {
            $dirIterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);

            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                if ($file->isDir()) {
                    continue;
                }

                if (!$this->toManyFiles && ++$filesCout > self::TO_MANY_FILES_WARNING_LIMIT) {
                    $this->toManyFiles = true;
                    $this->worker->doLog('There are too many files. This makes file monitoring very slow. Install php-inotify extension to increase performance.', 'WARNING');
                }

                if (!$this->checkPattern($file->getFilename())) {
                    continue;
                }

                if ($file->getFileInfo()->getMTime() > $this->lastMTime) {
                    $this->lastMTime = $file->getFileInfo()->getMTime();
                    $this->reboot();
                    return;
                }
            }
        }
    }
}
