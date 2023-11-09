<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\HttpKernel\KernelInterface;

final class KernelRunner
{
    /** @var resource */
    private $stream;

    public function __construct(private KernelInterface $kernel)
    {
    }

    public function setOutputStream($stream): self
    {
        $this->stream = $stream;

        return $this;
    }

    public function start(bool $isDaemon = false): void
    {
        $command = $isDaemon ? 'start -d' : 'start';
        $this->kernel->isDebug() ? $this->runInSeparateProcess($command) : $this->runInCurrentProcess($command);
    }

    public function stop(): void
    {
        $this->runInCurrentProcess('stop');
    }

    public function status(): void
    {
        $this->runInCurrentProcess('status');
    }

    private function runInSeparateProcess(string $command): void
    {
        $refl = new \ReflectionClass(self::class);
        $entryPointPath = dirname($refl->getFileName()) . DIRECTORY_SEPARATOR . 'app.php';

        $envs = [
            'WORKERMAN_PROJECT_DIR' => $this->kernel->getProjectDir(),
            'WORKERMAN_KERNEL_CLASS' => $this->kernel::class,
            'WORKERMAN_APP_ENV' => $this->kernel->getEnvironment(),
            'WORKERMAN_APP_DEBUG' => ($this->kernel->isDebug() ? '1' : '0'),
        ];

        $cmd = [PHP_BINARY, $entryPointPath, $command, '2>&1'];
        if (!Utils::isWindows()) {
            array_unshift($cmd, 'exec');
        }

        $descriptorspec = [1 => ['pipe', 'w']];
        $process = proc_open(implode(' ', $cmd), $descriptorspec, $pipes, null, $envs);
        stream_set_blocking($pipes[1], false);

        $init = 10;
        $step = 1000;
        $max = 100000;
        $i = $init;
        while(1) {
            $line = fgets($pipes[1]);

            if ($line !== false) {
                fputs($this->stream, $line);
                $i = $init;
                continue;
            }

            if (proc_get_status($process)['running'] === false) {
                break;
            }

            if (($i+=$step) >= $max) {
                $i = $max;
            }

            usleep($i);
        }

        proc_close($process);
    }

    private function runInCurrentProcess(string $command): void
    {
        $runtime = new Runtime([
            'project_dir' => $this->kernel->getProjectDir(),
            'extended_ui' => true,
            'command' => $command,
            'stream' => $this->stream,
        ]);

        [$app, $args] = $runtime->getResolver(fn() => $this->kernel)->resolve();
        $runner = $runtime->getRunner($app(...$args));
        $runner->run();
    }
}
