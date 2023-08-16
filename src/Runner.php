<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Luzrain\WorkermanBundle\Worker\FileMonitorWorker;
use Luzrain\WorkermanBundle\Worker\HttpServerWorker;
use Luzrain\WorkermanBundle\Worker\SchedulerWorker;
use Luzrain\WorkermanBundle\Worker\SupervisorWorker;
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

        // Start http server
        if ($config['webserver']['processes'] > 0) {
            new HttpServerWorker($this->kernelFactory, $config);
        }

        // Start scheduler worker
        if (!empty($schedulerConfig)) {
            new SchedulerWorker($this->kernelFactory, $config, $schedulerConfig);
        }

        // Start File monitor worker
        //if ($kernel->isDebug() && !Worker::$daemonize) {
        if (true && !Worker::$daemonize) {
            new FileMonitorWorker($config);
        }

        // Start user process workers (Windows does not support custom processes)
        if (!empty($processConfig) && !Utils::isWindows()) {
            new SupervisorWorker($this->kernelFactory, $config, $processConfig);
        }

        Worker::runAll();

        return 0;
    }
}
