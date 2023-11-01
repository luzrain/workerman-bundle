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

    public static function checkMasterIsAlive($master_pid): bool
    {
        return parent::checkMasterIsAlive($master_pid);
    }
}
