<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Luzrain\WorkermanBundle\DependencyInjection\WorkermanExtension;

final class WorkermanBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new WorkermanExtension();
    }
}
