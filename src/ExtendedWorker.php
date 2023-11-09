<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
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

    public static function log(mixed $msg, bool $decorated = false): void
    {
        if (!self::$extendedUi) {
            parent::log($msg, $decorated);
            return;
        }

        if ($msg instanceof \Throwable) {
            $msg = FlattenException::createFromThrowable($msg)->getAsString();
            $level = 'EMERGENCY';
        } else {
            $level = 'INFO';
        }

        parent::safeEcho("LOG:$level:" . serialize($msg) . "\n");
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

    public static function checkMasterIsAlive(int $masterPid): bool
    {
        return parent::checkMasterIsAlive($masterPid);
    }

    public static function setOutputStream($stream): void
    {
        self::$outputStream = $stream;
    }
}
