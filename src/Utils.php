<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

/**
 * @internal
 */
final class Utils
{
    private function __construct()
    {
    }

    public static function cpuCount(): int
    {
        // Windows does not support the number of processes setting.
        if (self::isWindows()) {
            return 1;
        }

        if (!is_callable('shell_exec')) {
            return 1;
        }

        return \strtolower(\PHP_OS) === 'darwin'
            ? (int) shell_exec('sysctl -n machdep.cpu.core_count')
            : (int) shell_exec('nproc')
        ;
    }

    public static function isWindows(): bool
    {
        return \DIRECTORY_SEPARATOR !== '/';
    }

    public static function reboot(bool $rebootAllWorkers = false): void
    {
        posix_kill($rebootAllWorkers ? posix_getppid() : posix_getpid(), SIGUSR1);
    }

    public static function clearOpcache(): void
    {
        if (function_exists('opcache_get_status') && $status = opcache_get_status()) {
            foreach (array_keys($status['scripts'] ?? []) as $file) {
                opcache_invalidate($file, true);
            }
        }
    }

    public static function getPid(string $pidFile): int
    {
        return is_file($pidFile) ? (int) @file_get_contents($pidFile) : 0;
    }
}
