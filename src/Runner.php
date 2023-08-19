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
        // Warm up cache if no cache directory found
        // Do it in a forked process as the main process should not boot kernel
        if (!is_dir($this->kernelFactory->getCacheDir())) {
            if (\pcntl_fork() === 0) {
                $this->kernelFactory->createKernel()->boot();
                exit;
            } else {
                pcntl_wait($status);
                unset($status);
            }
        }

        $configLoader = new ConfigLoader($this->kernelFactory->getCacheDir(), $this->kernelFactory->isDebug());
        $config = $configLoader->getWorkermanConfig();
        $schedulerConfig = $configLoader->getSchedulerConfig();
        $processConfig = $configLoader->getProcessConfig();

        if (!is_dir($varRunDir = dirname($config['pid_file']))) {
            mkdir(directory: $varRunDir, recursive: true);
        }

        TcpConnection::$defaultMaxPackageSize = $config['max_package_size'];
        Worker::$pidFile = $config['pid_file'] ?? '';
        Worker::$logFile = $config['log_file'];
        Worker::$stdoutFile = $config['stdout_file'];
        Worker::$stopTimeout = $config['stop_timeout'];

        if ($config['webserver']['processes'] === null || $config['webserver']['processes'] > 0) {
            $config['webserver']['processes'] ??= Utils::cpuCount() * 2;
            HttpServer::run($this->kernelFactory, $config);
        }

        if (!empty($schedulerConfig)) {
            Scheduler::run($this->kernelFactory, $config, $schedulerConfig);
        }

        if (in_array('file_monitor', $config['webserver']['relod_strategy']) && $this->kernelFactory->isDebug()) {
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
