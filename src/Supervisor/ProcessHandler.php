<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Supervisor;

use Luzrain\WorkermanBundle\Event\ProcessErrorEvent;
use Luzrain\WorkermanBundle\Event\ProcessStartEvent;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ProcessHandler
{
    public function __construct(
        private ContainerInterface $locator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $service, string $processName): void
    {
        [$serviceName, $method] = explode('::', $service, 2);
        $service = $this->locator->get($serviceName);

        $this->eventDispatcher->dispatch(new ProcessStartEvent($service::class, $processName));

        try {
            $service->$method();
        } catch (\Throwable $e) {
            $this->eventDispatcher->dispatch(new ProcessErrorEvent($e, $service::class, $processName));
        }
    }
}
