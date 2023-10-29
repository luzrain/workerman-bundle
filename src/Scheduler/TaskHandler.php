<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Scheduler;

use Luzrain\WorkermanBundle\Events\TaskErrorEvent;
use Luzrain\WorkermanBundle\Events\TaskStartEvent;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class TaskHandler
{
    public function __construct(
        private ContainerInterface $locator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $service, string $taskName): void
    {
        [$serviceName, $method] = explode('::', $service, 2);
        $service = $this->locator->get($serviceName);

        $this->eventDispatcher->dispatch(new TaskStartEvent($service::class, $taskName));

        try {
            $service->$method();
        } catch (\Throwable $e) {
            $this->eventDispatcher->dispatch(new TaskErrorEvent($e, $service::class, $taskName));
        }
    }
}
