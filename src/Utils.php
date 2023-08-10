<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

final class Utils
{
    public static function cpuCount(): int
    {
        // Windows does not support the number of processes setting.
        if (\DIRECTORY_SEPARATOR === '\\') {
            return 1;
        }

        if (!\is_callable('shell_exec')) {
            return 1;
        }

        return \strtolower(PHP_OS) === 'darwin'
            ? (int) \shell_exec('sysctl -n machdep.cpu.core_count')
            : (int) \shell_exec('nproc')
        ;
    }
}
