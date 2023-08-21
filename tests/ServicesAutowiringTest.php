<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Test;

use Luzrain\WorkermanBundle\Reboot\RebootStrategyInterface;
use Luzrain\WorkermanBundle\Reboot\StackRebootStrategy;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ServicesAutowiringTest extends KernelTestCase
{
    public function testServiceAutowiring(): void
    {
        $container = self::getContainer();

        $this->assertInstanceOf(ContainerInterface::class, $container->get('workerman.process_locator'));
        $this->assertInstanceOf(ContainerInterface::class, $container->get('workerman.scheduledjob_locator'));
        $this->assertInstanceOf(StackRebootStrategy::class, $container->get(RebootStrategyInterface::class));
    }
}
