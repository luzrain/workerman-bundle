<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Workerman\Worker;

final class ExtendedWorker extends Worker
{
    public static bool $extendedInterface = false;

    protected static function displayUI(): void
    {
        if (!self::$extendedInterface) {
            parent::displayUI();
            return;
        }

        $data = [];
        foreach (static::$_workers as $worker) {
            $data[] = [
                'user' => $worker->user,
                'worker' => $worker->name,
                'socket' => $worker->parseSocketAddress() ?? '-',
                'processes' => $worker->count,
            ];
        }

        parent::safeEcho('HEADER:' . serialize($data) . "\n");
    }

    public static function log(mixed $msg): void
    {
        if (!self::$extendedInterface) {
            parent::log($msg);
            return;
        }

        if ($msg instanceof \Throwable) {
            self::logWithLevel('EMERGENCY', $msg->getMessage());
        } else {
            self::logWithLevel('INFO', $msg);
        }
    }

    public static function logWithLevel(string $level, mixed $msg): void
    {
        if (!self::$extendedInterface) {
            parent::log($msg);
            return;
        }

        parent::log("LOG:$level:" . serialize($msg));
    }

    public static function getEventLoopClass(): string
    {
        return parent::getEventLoopName();
    }

    // @TODO Remove in v5
    protected static function installSignal(): void
    {
        if (static::$_OS !== \OS_TYPE_LINUX) {
            return;
        }
        $signalHandler = static::signalHandler(...);
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
