<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Reboot\FileMonitorWatcher;

use Luzrain\WorkermanBundle\Utils;

abstract class FileMonitorWatcher
{
    private array $sourceDir;
    private array $filePattern;
    private \Closure $logger;

    public static function create(array $sourceDir, array $filePattern, \Closure $logger): self
    {
        return \extension_loaded('inotify')
            ? new InotifyMonitorWatcher($sourceDir, $filePattern, $logger)
            : new PollingMonitorWatcher($sourceDir, $filePattern, $logger)
        ;
    }

    public function __construct(array $sourceDir, array $filePattern, \Closure $logger)
    {
        $this->sourceDir = array_filter($sourceDir, is_dir(...));
        $this->filePattern = $filePattern;
        $this->logger = $logger;
    }

    abstract public function start(): void;

    final protected function getSourceDir(): array
    {
        return $this->sourceDir;
    }

    final protected function checkPattern(string $filename): bool
    {
        foreach ($this->filePattern as $pattern) {
            if (\fnmatch($pattern, $filename)) {
                return true;
            }
        }

        return false;
    }

    final protected function log(string $message): void
    {
        ($this->logger)($message);
    }

    final protected function reboot(): void
    {
        Utils::reboot(rebootAllWorkers: true);
    }
}
