<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Luzrain\WorkermanBundle\Runner\FileMonitor;
use Luzrain\WorkermanBundle\Runner\HttpServer;
use Luzrain\WorkermanBundle\Runner\Scheduler;
use Luzrain\WorkermanBundle\Runner\Supervisor;
use Symfony\Component\Runtime\RunnerInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

final class Runner implements RunnerInterface
{
    public function __construct(private KernelFactory $kernelFactory)
    {
    }

    public function run(): int
    {
        //$configLoader = new ConfigLoader($kernel->getCacheDir(), $kernel->isDebug());
        $configLoader = new ConfigLoader('/app/var/cache/dev', true);
        $config = $configLoader->getWorkermanConfig();
        $schedulerConfig = $configLoader->getSchedulerConfig();
        $processConfig = $configLoader->getProcessConfig();

        $varRunDir = dirname($config['pid_file']);
        if (!is_dir($varRunDir)) {
            mkdir($varRunDir);
        }

        TcpConnection::$defaultMaxPackageSize = $config['max_package_size'];
        Worker::$pidFile = $config['pid_file'] ?? '';
        Worker::$logFile = $config['log_file'];
        Worker::$stdoutFile = $config['stdout_file'];
        Worker::$stopTimeout = $config['stop_timeout'];

        if ($config['webserver']['processes'] > 0) {
            HttpServer::run($this->kernelFactory, $config);
        }

        if (!empty($schedulerConfig)) {
            Scheduler::run($this->kernelFactory, $config, $schedulerConfig);
        }

        //if ($kernel->isDebug() && !Worker::$daemonize) {
        if (true && !Worker::$daemonize) {
            FileMonitor::run($config);
        }

        // Windows does not support custom processes
        if (!empty($processConfig) && !Utils::isWindows()) {
            Supervisor::run($this->kernelFactory, $config, $processConfig);
        }

        Worker::runAll();

        return 0;
    }
}
