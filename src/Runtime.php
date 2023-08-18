<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\Runtime\ResolverInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

class Runtime extends SymfonyRuntime
{
    public function getRunner(object|null $application): RunnerInterface
    {
        if ($application instanceof KernelFactory) {
            return new Runner($application);
        }

        return parent::getRunner($application);
    }

    public function getResolver(callable $callable, \ReflectionFunction|null $reflector = null): ResolverInterface
    {
        $resolver = parent::getResolver($callable, $reflector);

        return new Resolver($resolver, $this->options);
    }
}
