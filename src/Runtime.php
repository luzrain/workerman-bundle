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
            $command = $this->options['command'] ?? '';
            $extendedUi = $this->options['extended_ui'] ?? false;
            $stream = $this->options['stream'] ?? null;

            return new Runner($application, $extendedUi, $command, $stream);
        }

        return parent::getRunner($application);
    }

    public function getResolver(callable $callable, \ReflectionFunction|null $reflector = null): ResolverInterface
    {
        $resolver = parent::getResolver($callable, $reflector);

        return new Resolver($resolver, $this->options);
    }
}
