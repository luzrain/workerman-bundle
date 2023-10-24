<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\HttpKernel\KernelInterface;

final class KernelRunner
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    public function run(): int
    {
        $applicationKernel = $this->kernel;

        $runtime = new Runtime(($_SERVER['APP_RUNTIME_OPTIONS'] ?? $_ENV['APP_RUNTIME_OPTIONS'] ?? []) + [
            'project_dir' => $applicationKernel->getProjectDir(),
        ]);

        [$app, $args] = $runtime->getResolver(fn() => $applicationKernel)->resolve();
        $application = $app(...$args);

        return $runtime->getRunner($application)->run();
    }
}
