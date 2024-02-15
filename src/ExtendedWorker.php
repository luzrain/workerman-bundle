<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Workerman\Worker;

final class ExtendedWorker extends Worker
{
    public function doLog(mixed $msg, string $level = 'INFO'): void
    {
        parent::log($this->name . ' ' . $msg);
    }

    // @TODO Remove in v5
    protected static function installSignal(): void
    {
        if (self::$_OS !== \OS_TYPE_LINUX) {
            return;
        }
        $signalHandler = self::signalHandler(...);
        \pcntl_signal(\SIGINT, $signalHandler, false);
        \pcntl_signal(\SIGTERM, $signalHandler, false);
        \pcntl_signal(\SIGHUP, $signalHandler, false);
        \pcntl_signal(\SIGTSTP, $signalHandler, false);
        \pcntl_signal(\SIGQUIT, $signalHandler, false);
        \pcntl_signal(\SIGUSR1, $signalHandler, false);
        \pcntl_signal(\SIGUSR2, $signalHandler, false);
        \pcntl_signal(\SIGIOT, $signalHandler, false);
        \pcntl_signal(\SIGIO, $signalHandler, false);
        \pcntl_signal(\SIGPIPE, \SIG_IGN, false);
    }
}
