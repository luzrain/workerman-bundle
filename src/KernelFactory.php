<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\HttpKernel\KernelInterface;

final class KernelFactory
{
    private string $projectDir;
    private string $environment;
    private bool $isDebug;

    public function __construct(private \Closure $app, private array $args, array $options)
    {
        $this->projectDir = $options['project_dir'];
        $this->environment = $_SERVER[$options['env_var_name']];
        $this->isDebug = (bool) $_SERVER[$options['debug_var_name']];
    }

    public function createKernel(): KernelInterface
    {
        return ($this->app)(...$this->args);
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->getEnvironment();
    }
}
