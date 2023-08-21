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
        $configLoader = new ConfigLoader(
            projectDir: $this->kernelFactory->getProjectDir(),
            cacheDir: $this->kernelFactory->getCacheDir(),
            isDebug: $this->kernelFactory->isDebug(),
        );

        // Warm up cache if no workerman fresh config found (do it in a forked process as the main process should not boot kernel)
        if (!$configLoader->isFresh()) {
            if (\pcntl_fork() === 0) {
                $this->kernelFactory->createKernel()->boot();
                exit;
            } else {
                pcntl_wait($status);
                unset($status);
            }
        }

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
            new HttpServerWorker($this->kernelFactory, $config);
        }

        if (!empty($schedulerConfig)) {
            new SchedulerWorker($this->kernelFactory, $config, $schedulerConfig);
        }

        if (
            in_array('file_monitor', $config['webserver']['relod_strategy'])
            && $this->kernelFactory->isDebug()
            && $this->kernelFactory->getEnvironment() !== 'test'
        ) {
            new FileMonitorWorker(
                sourceDir: $config['relod_strategy']['file_monitor']['source_dir'],
                filePattern: $config['relod_strategy']['file_monitor']['file_pattern'],
            );
        }

        if (!empty($processConfig)) {
            new SupervisorWorker($this->kernelFactory, $config, $processConfig);
        }

        Worker::runAll();

        return 0;
    }
}
