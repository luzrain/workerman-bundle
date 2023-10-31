<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class ProcessStartEvent extends Event
{
    public function __construct(private string $serviceClass, private string $processName)
    {
    }

    public function getProcessName(): string
    {
        return $this->processName;
    }

    public function getServiceClass(): string
    {
        return $this->serviceClass;
    }
}
