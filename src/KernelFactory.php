<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\HttpKernel\KernelInterface;

final class KernelFactory
{
    public function __construct(private \Closure $app, private array $args)
    {
    }

    public function createKernel(): KernelInterface
    {
        return ($this->app)(...$this->args);
    }
}
