<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Luzrain\WorkermanBundle\KernelFactory;
use Workerman\Worker;

final class HttpServerWorker
{
    protected const PROCESS_TITLE = 'Server';

    public function __construct(KernelFactory $kernelFactory, array $config)
    {
        $listen = $config['listen'] ?? '';
        $transport = 'tcp';
        $context = [];

        if (str_starts_with($listen, 'https://')) {
            $listen = str_replace('https://', 'http://', $listen);
            $transport = 'ssl';
            $context = [
                'ssl' => [
                    'local_cert' => $config['local_cert'] ?? '',
                    'local_pk' => $config['local_pk'] ?? '',
                ],
            ];
        } elseif (str_starts_with($listen, 'ws://')) {
            $listen = str_replace('ws://', 'websocket://', $listen);
        } elseif (str_starts_with($listen, 'wss://')) {
            $listen = str_replace('wss://', 'websocket://', $listen);
            $transport = 'ssl';
            $context = [
                'ssl' => [
                    'local_cert' => $config['local_cert'] ?? '',
                    'local_pk' => $config['local_pk'] ?? '',
                ],
            ];
        }

        $worker = new Worker($listen, $context);
        $worker->name = sprintf('[%s] "%s"', self::PROCESS_TITLE, $config['name']);
        $worker->user = $config['user'] ?? '';
        $worker->group = $config['group'] ?? '';
        $worker->count = $config['processes'];
        $worker->transport = $transport;
        $worker->onWorkerStart = function (Worker $worker) use ($kernelFactory, $config) {
            Worker::log(sprintf('[%s] "%s" started', self::PROCESS_TITLE, $config['name']));
            $kernel = $kernelFactory->createKernel();
            $kernel->boot();
            $worker->onMessage = $kernel->getContainer()->get($config['handler']);
        };
    }
}
