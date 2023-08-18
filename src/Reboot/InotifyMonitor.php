<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Reboot;

use Workerman\Events\EventInterface;
use Workerman\Timer;
use Workerman\Worker;

final class InotifyMonitor
{
    private const RELOAD_TIMER = 0.33;
    private mixed $fd;
    private array $pathByWd = [];
    private \Closure|null $reloadCallback = null;

    public function __construct()
    {
        $this->fd = inotify_init();
        stream_set_blocking($this->fd, false);
        $dirIterator = new \RecursiveDirectoryIterator('/app/src', \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $this->watchDir($file->getPathname());
            }
        }

        Worker::$globalEvent->add($this->fd, EventInterface::EV_READ, $this->onNotify(...));
    }

    public function onNotify(mixed $inotifyFd): void
    {
        $events = inotify_read($inotifyFd) ?: [];

        foreach($events as $event) {
            if ($this->isFlagSet($event['mask'], IN_IGNORED)) {
                unset($this->pathByWd[$event['wd']]);
                continue;
            }

            $path = $this->pathByWd[$event['wd']] . '/' . $event['name'];

            if ($this->isFlagSet($event['mask'], IN_CREATE | IN_ISDIR)) {
                $this->watchDir($path);
                continue;
            }

            if ($this->reloadCallback === null) {
                $this->reloadCallback = function () {
                    $this->reloadCallback = null;
                    $this->reload();
                };
                Timer::add(self::RELOAD_TIMER, $this->reloadCallback, [], false);
            }
        }
    }

    private function watchDir(string $path): void
    {
        $wd = inotify_add_watch($this->fd, $path, IN_MODIFY | IN_CREATE | IN_DELETE | IN_MOVED_TO);
        $this->pathByWd[$wd] = $path;
    }

    private function isFlagSet(int $check, int $flag): bool
    {
        return ($check & $flag) == $flag;
    }

    private function reload(): void
    {
        if (function_exists('opcache_get_status')) {
            if ($status = \opcache_get_status()) {
                if (isset($status['scripts']) && $scripts = $status['scripts']) {
                    foreach (array_keys($scripts) as $file) {
                        \opcache_invalidate($file, true);
                    }
                }
            }
        }

        posix_kill(\posix_getppid(), SIGUSR1);
    }
}
