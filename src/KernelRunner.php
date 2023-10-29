<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\HttpKernel\KernelInterface;

final class KernelRunner
{
    public const ENTRYPOINT = 'app.php';

    private string $entryPointPath;
    private string $projectDir;
    private string $kernelClass;
    private string $env;
    private bool $debug;

    /** @var resource|null */
    private mixed $pipe = null;

    public function __construct(KernelInterface $kernel)
    {
        $refl = new \ReflectionClass(self::class);
        $this->entryPointPath = dirname($refl->getFileName()) . DIRECTORY_SEPARATOR . self::ENTRYPOINT;
        $this->projectDir = $kernel->getProjectDir();
        $this->kernelClass = $kernel::class;
        $this->env = $kernel->getEnvironment();
        $this->debug = $kernel->isDebug();
    }

    public function runStart(bool $isDaemon = false): void
    {
        $this->run($isDaemon ? 'start -d' : 'start');
    }

    public function runStop(): void
    {
        $this->run('stop');
    }

    public function runStatus(): void
    {
        $this->run('status');
    }

    private function run(string $command): void
    {
        $cmd = [];
        $cmd[] = 'WORKERMAN_PROJECT_DIR="' . $this->projectDir . '"';
        $cmd[] = 'WORKERMAN_KERNEL_CLASS="' . $this->kernelClass . '"';
        $cmd[] = 'WORKERMAN_APP_ENV=' . $this->env;
        $cmd[] = 'WORKERMAN_APP_DEBUG=' . ($this->debug ? '1' : '0');
        if (!Utils::isWindows()) {
            $cmd[] = 'exec';
        }
        $cmd[] = PHP_BINARY;
        $cmd[] = $this->entryPointPath;
        $cmd[] = $command;
        $cmd[] = '2>&1';

        $this->pipe = popen(implode(' ', $cmd), 'rb');
    }

    public function readOutput(): \Generator
    {
        if ($this->pipe === null) {
            throw new \LogicException('There is no running process to read');
        }

        while(!feof($this->pipe)) {
            $line = fread($this->pipe, 512000);
            $tok = strtok($line, "\n");
            while ($tok !== false) {
                yield $tok;
                $tok = strtok("\n");
            }
        }

        pclose($this->pipe);
        $this->pipe = null;
    }

    public function wait(): void
    {
        foreach ($this->readOutput() as $line) {
            // do nothing
        }
    }
}
