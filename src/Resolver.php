<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\Runtime\ResolverInterface;

final class Resolver implements ResolverInterface
{
    public function __construct(private ResolverInterface $resolver)
    {
    }

    public function resolve(): array
    {
        // Called in "autoload_runtime.php"
        [$app, $args] = $this->resolver->resolve();

        return [static fn() => new KernelFactory($app, $args), []];
    }
}
