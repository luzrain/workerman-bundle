<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

final class Runtime extends SymfonyRuntime
{
    public function getRunner(object|null $application): RunnerInterface
    {
        if ($application instanceof KernelInterface) {
            return new Runner($application);
        }

        return parent::getRunner($application);
    }
}
