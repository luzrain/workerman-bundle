<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Reboot\FileMonitorWatcher;

use Workerman\Events\EventInterface;
use Workerman\Timer;
use Workerman\Worker;

final class InotifyMonitorWatcher extends FileMonitorWatcher
{
    private const REBOOT_DELAY = 0.33;
    private mixed $fd;
    private array $pathByWd = [];
    private \Closure|null $rebootCallback = null;

    public function start(): void
    {
        $this->fd = \inotify_init();
        stream_set_blocking($this->fd, false);

        foreach ($this->sourceDir as $dir) {
            $dirIterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);

            $this->watchDir($dir);

            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                if ($file->isDir()) {
                    $this->watchDir($file->getPathname());
                }
            }
        }

        Worker::$globalEvent->add($this->fd, EventInterface::EV_READ, $this->onNotify(...));
    }

    /**
     * @param resource $inotifyFd
     */
    private function onNotify(mixed $inotifyFd): void
    {
        $events = \inotify_read($inotifyFd) ?: [];

        if ($this->rebootCallback !== null) {
            return;
        }

        foreach ($events as $event) {
            if ($this->isFlagSet($event['mask'], IN_IGNORED)) {
                unset($this->pathByWd[$event['wd']]);
                continue;
            }

            if ($this->isFlagSet($event['mask'], IN_CREATE | IN_ISDIR)) {
                $this->watchDir($this->pathByWd[$event['wd']] . '/' . $event['name']);
                continue;
            }

            if (!$this->checkPattern($event['name'])) {
                continue;
            }

            $this->rebootCallback = function () {
                $this->rebootCallback = null;
                $this->reboot();
            };

            Timer::add(self::REBOOT_DELAY, $this->rebootCallback, [], false);

            return;
        }
    }

    private function watchDir(string $path): void
    {
        $wd = \inotify_add_watch($this->fd, $path, IN_MODIFY | IN_CREATE | IN_DELETE | IN_MOVED_TO);
        $this->pathByWd[$wd] = $path;
    }

    private function isFlagSet(int $check, int $flag): bool
    {
        return ($check & $flag) === $flag;
    }
}
