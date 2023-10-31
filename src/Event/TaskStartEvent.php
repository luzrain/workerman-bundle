<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class TaskStartEvent extends Event
{
    public function __construct(private string $serviceClass, private string $taskName)
    {
    }

    public function getTaskName(): string
    {
        return $this->taskName;
    }

    public function getServiceClass(): string
    {
        return $this->serviceClass;
    }
}
