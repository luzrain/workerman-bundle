<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\Runtime\ResolverInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

final class Runtime extends SymfonyRuntime
{
    public function getRunner(object|null $application): RunnerInterface
    {
        assert($application instanceof KernelFactory);

        return new Runner($application);
    }

    public function getResolver(callable $callable, \ReflectionFunction|null $reflector = null): ResolverInterface
    {
        $context = $_SERVER + $_ENV;

        return new Resolver($callable, $context, $this->options);
    }
}
