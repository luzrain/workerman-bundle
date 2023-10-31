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

        $data = [
            'version' => self::VERSION,
            'eventLoop' => self::getEventLoopName(),
            'workers' => [],
        ];
        foreach (self::getAllWorkers() as $worker) {
            $data['workers'][] = [
                'user' => $worker->user,
                'worker' => $worker->name,
                'socket' => $worker->parseSocketAddress() ?? '-',
                'processes' => $worker->count,
            ];
        }

        parent::safeEcho('HEADER:' . serialize($data) . "\n");
    }

    public static function log(mixed $msg, bool $decorated = false): void
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

    public static function checkMasterIsAlive($master_pid): bool
    {
        return parent::checkMasterIsAlive($master_pid);
    }

    public static function checkMasterIsAlive($master_pid): bool
    {
        return parent::checkMasterIsAlive($master_pid);
    }
}
