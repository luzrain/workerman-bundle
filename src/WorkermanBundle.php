<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Luzrain\WorkermanBundle\DependencyInjection\CompilerPass;
use Luzrain\WorkermanBundle\DependencyInjection\WorkermanExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class WorkermanBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CompilerPass());
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new WorkermanExtension();
    }
}
