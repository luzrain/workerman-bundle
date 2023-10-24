<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\Runtime\ResolverInterface;

final class Resolver implements ResolverInterface
{
    public function __construct(private ResolverInterface $resolver, private array $options)
    {
    }

    public function resolve(): array
    {
        [$app, $args] = $this->resolver->resolve();

        return [static fn(...$args) => new KernelFactory(...$args), [$app, $args, $this->options]];
    }
}
