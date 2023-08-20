<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

final class Utils
{
    public static function cpuCount(): int
    {
        // Windows does not support the number of processes setting.
        if (self::isWindows()) {
            return 1;
        }

        if (!\is_callable('shell_exec')) {
            return 1;
        }

        return \strtolower(\PHP_OS) === 'darwin'
            ? (int) \shell_exec('sysctl -n machdep.cpu.core_count')
            : (int) \shell_exec('nproc')
        ;
    }

    public static function isWindows(): bool
    {
        return \DIRECTORY_SEPARATOR !== '/';
    }

    public static function reboot(bool $rebootAllWorkers = false, bool $clearOpcache = false): void
    {
        if ($clearOpcache && function_exists('opcache_get_status')) {
            if ($status = \opcache_get_status()) {
                foreach (array_keys($status['scripts'] ?? []) as $file) {
                    \opcache_invalidate($file, true);
                }
            }
        }

        if ($rebootAllWorkers) {
            posix_kill(posix_getppid(), SIGUSR1);
        } else {
            posix_kill(posix_getpid(), SIGUSR1);
        }
    }
}
