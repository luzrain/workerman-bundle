<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Test;

use Luzrain\WorkermanBundle\Http\HttpRequestHandler;
use Luzrain\WorkermanBundle\Scheduler\TaskHandler;
use Luzrain\WorkermanBundle\Supervisor\ProcessHandler;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ServicesAutowiringTest extends KernelTestCase
{
    public function testServiceAutowiring(): void
    {
        $container = self::getContainer();

        $this->assertInstanceOf(ContainerInterface::class, $container->get('workerman.process_locator'));
        $this->assertInstanceOf(ContainerInterface::class, $container->get('workerman.task_locator'));
        $this->assertInstanceOf(HttpRequestHandler::class, $container->get('workerman.http_request_handler'));
        $this->assertInstanceOf(TaskHandler::class, $container->get('workerman.task_handler'));
        $this->assertInstanceOf(ProcessHandler::class, $container->get('workerman.process_handler'));
    }
}
