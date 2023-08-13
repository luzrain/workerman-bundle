<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Luzrain\WorkermanBundle\Worker\SchedulerWorker;
use Luzrain\WorkermanBundle\Worker\HttpServerWorker;
use Luzrain\WorkermanBundle\Worker\SupervisorWorker;
use Psr\Container\ContainerInterface;
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
        Worker::$onMasterReload = $this->onMasterReload(...);

        // Start http server
        if ($config['webserver']['processes'] > 0) {
            new HttpServerWorker(new RequestHandler($this->kernel), $config);
        }

        // Start scheduler worker
        if (!empty($schedulerConfig)) {
            /** @var ContainerInterface $scheduledJobsLocator */
            $scheduledJobsLocator = $this->kernel->getContainer()->get('workerman.scheduledjob_locator');
            new SchedulerWorker($scheduledJobsLocator, $config, $schedulerConfig);
        }

        // Start File monitor worker
//        if ($this->kernel->isDebug() && !Worker::$daemonize) {
//            new FileMonitorWorker($config);
//        }

        // Start user process workers (Windows does not support custom processes)
        if (!empty($processConfig) && !Utils::isWindows()) {
            /** @var ContainerInterface $processLocator */
            $processLocator = $this->kernel->getContainer()->get('workerman.process_locator');
            new SupervisorWorker($processLocator, $config, $processConfig);
        }

        Worker::runAll();

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
}
