<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Reboot;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ExceptionRebootStrategy implements RebootStrategyInterface
{
    private \Throwable|null $exception = null;

    /**
     * @param array<class-string> $allowedExceptions
     */
    public function __construct(private array $allowedExceptions = [])
    {
    }

    public function onException(ExceptionEvent $event): void
    {
        if ($event->getRequestType() !== HttpKernelInterface::MAIN_REQUEST) {
            return;
        }

        foreach ($this->allowedExceptions as $allowedExceptionClass) {
            if ($event->getThrowable() instanceof $allowedExceptionClass) {
                return;
            }
        }

        $this->exception = $event->getThrowable();
    }

    public function shouldReboot(): bool
    {
        return $this->exception !== null;
    }
}
