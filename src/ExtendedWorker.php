<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Workerman\Worker;

final class ExtendedWorker extends Worker
{
    public static bool $extendedUi = false;

    protected static function displayUI(): void
    {
        if (!self::$extendedUi) {
            parent::displayUI();
            return;
        }

        $data = [
            'version' => self::VERSION,
            'eventLoop' => self::getEventLoopName(),
            'workers' => [],
        ];

        foreach (self::$_workers as $worker) {
            $data['workers'][] = [
                'user' => $worker->user,
                'worker' => $worker->name,
                'socket' => $worker->parseSocketAddress() ?? '-',
                'processes' => $worker->count,
            ];
        }

        parent::safeEcho('HEADER:' . serialize($data) . "\n");
    }

    public function doLog(mixed $msg, string $level = 'INFO'): void
    {
        if (!self::$extendedUi) {
            parent::log($this->name . ' ' . $msg);
            return;
        }

        if ($msg instanceof \Throwable) {
            $msg = $msg->getMessage();
            $level = 'EMERGENCY';
        }

        parent::safeEcho("LOG:$level:" . serialize($this->name . ' ' . $msg) . "\n");
    }

    public static function checkMasterIsAlive($master_pid): bool
    {
        return parent::checkMasterIsAlive($master_pid);
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
