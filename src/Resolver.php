<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\Runtime\ResolverInterface;

final class Resolver implements ResolverInterface
{
    public function __construct(private \Closure $app, private array $context, private array $options)
    {
    }

    public function resolve(): array
    {
        return [static fn(...$args) => new KernelFactory(...$args), [$this->app, $this->context, $this->options]];
    }
}
