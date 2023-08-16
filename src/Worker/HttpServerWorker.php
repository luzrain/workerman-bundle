<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Worker;

use Luzrain\WorkermanBundle\KernelFactory;
use Luzrain\WorkermanBundle\RequestHandler;
use Workerman\Worker;

final class HttpServerWorker
{
    private const PROCESS_NAME = 'WebServer';

    public function __construct(private KernelFactory $kernelFactory, array $config)
    {
        if (str_starts_with($config['webserver']['listen'], 'https://')) {
            $listen = str_replace('https://', 'http://', $config['webserver']['listen']);
            $transport = 'ssl';
            $context = [
                'ssl' => [
                    'local_cert' => $config['webserver']['local_cert'] ?? '',
                    'local_pk' => $config['webserver']['local_pk'] ?? '',
                ]
            ];
        } else {
            $listen = $config['webserver']['listen'];
            $transport = 'tcp';
            $context = [];
        }

        $worker = new Worker($listen, $context);
        $worker->name = sprintf('[%s] %s', self::PROCESS_NAME, $config['webserver']['name']);
        $worker->user = $config['user'] ?? '';
        $worker->group = $config['group'] ?? '';
        $worker->count = $config['webserver']['processes'];
        $worker->transport = $transport;
        $worker->onWorkerStart = function(Worker $worker) use ($config) {
            Worker::log(sprintf('[%s] "%s" Start', self::PROCESS_NAME, $config['webserver']['name']));
            $kernel = $this->kernelFactory->createKernel();
            $kernel->boot();
            $worker->onMessage = (new RequestHandler($kernel))(...);
        };
    }
}
