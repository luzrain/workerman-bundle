<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Events;

use Symfony\Contracts\EventDispatcher\Event;

final class TaskErrorEvent extends Event
{
    public function __construct(private \Throwable $error, private string $serviceClass, private $taskName)
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

    public function getError(): \Throwable
    {
        return $this->error;
    }

    public function setError(\Throwable $error): void
    {
        $this->error = $error;
    }
}
