<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

final class Runner implements RunnerInterface
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    public function run(): int
    {
        $this->kernel->boot();
        $configLoader = new ConfigLoader($this->kernel->getCacheDir(), $this->kernel->isDebug());
        $config = $configLoader->getConfig();
        $serverConfig = $config['server'];

        if (str_starts_with($serverConfig['listen'], 'https://')) {
            $listen = str_replace('https://', 'http://', $serverConfig['listen']);
            $transport = 'ssl';
            $context = ['ssl' => [
                'local_cert' => $serverConfig['local_cert'],
                'local_pk' => $serverConfig['local_pk'],
            ]];
        } else {
            $listen = $serverConfig['listen'];
            $transport = 'tcp';
            $context = [];
        }

        $worker = new Worker($listen, $context);

        TcpConnection::$defaultMaxPackageSize = $serverConfig['max_package_size'];
        $worker::$pidFile = $serverConfig['pid_file'];
        $worker::$logFile = $serverConfig['log_file'];
        $worker::$stdoutFile = $serverConfig['stdout_file'];
        $worker::$eventLoopClass = $serverConfig['event_loop'];
        $worker::$stopTimeout = $serverConfig['stop_timeout'];
        $worker->name = $serverConfig['name'];
        $worker->count = $serverConfig['processes'];
        $worker->user = $serverConfig['user'] ?? '';
        $worker->group = $serverConfig['group'] ?? '';
        $worker->transport = $transport;
        $worker::$onMasterReload = $this->onMasterReload(...);
        $worker->onWorkerStart = $this->onWorkerStart(...);

        $worker::runAll();

        return 0;
    }

    private function onMasterReload(): void
    {
        if (function_exists('opcache_get_status')) {
            if ($status = \opcache_get_status()) {
                if (isset($status['scripts']) && $scripts = $status['scripts']) {
                    foreach (array_keys($scripts) as $file) {
                        \opcache_invalidate($file, true);
                    }
                }
            }
        }
    }

    private function onWorkerStart(Worker $worker): void
    {
        $handler = new RequestHandler($this->kernel);
        $worker->onMessage = $handler->onMessage(...);
    }
}
