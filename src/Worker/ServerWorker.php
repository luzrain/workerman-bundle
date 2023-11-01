<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Luzrain\WorkermanBundle\ExtendedWorker as Worker;
use Luzrain\WorkermanBundle\KernelFactory;
use Luzrain\WorkermanBundle\Utils;

final class ServerWorker
{
    protected const PROCESS_TITLE = '[Server]';

    public function __construct(KernelFactory $kernelFactory, string|null $user, string|null $group, array $serverConfig)
    {
        $listen = $serverConfig['listen'] ?? '';
        $transport = 'tcp';
        $context = [];

        if (str_starts_with($listen, 'https://')) {
            $listen = str_replace('https://', 'http://', $listen);
            $transport = 'ssl';
            $context = [
                'ssl' => [
                    'local_cert' => $serverConfig['local_cert'] ?? '',
                    'local_pk' => $serverConfig['local_pk'] ?? '',
                ],
            ];
        } elseif (str_starts_with($listen, 'ws://')) {
            $listen = str_replace('ws://', 'websocket://', $listen);
        } elseif (str_starts_with($listen, 'wss://')) {
            $listen = str_replace('wss://', 'websocket://', $listen);
            $transport = 'ssl';
            $context = [
                'ssl' => [
                    'local_cert' => $serverConfig['local_cert'] ?? '',
                    'local_pk' => $serverConfig['local_pk'] ?? '',
                ],
            ];
        }

        $worker = new Worker($listen, $context);
        $worker->name = sprintf('%s "%s"', self::PROCESS_TITLE, $serverConfig['name']);
        $worker->user = $user ?? '';
        $worker->group = $group ?? '';
        $worker->count = $serverConfig['processes'] ?? Utils::cpuCount() * 2;
        $worker->transport = $transport;
        $worker->onWorkerStart = function (Worker $worker) use ($kernelFactory, $serverConfig) {
            $worker->doLog(sprintf('"%s" started', $serverConfig['name']));
            $kernel = $kernelFactory->createKernel();
            $kernel->boot();
            $worker->onMessage = $kernel->getContainer()->get('workerman.http_request_handler');
        };
    }
}
