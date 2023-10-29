<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Scheduler\Trigger;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class JitterTrigger implements TriggerInterface
{
    public function __construct(private readonly TriggerInterface $trigger, private readonly int $maxSeconds)
    {
    }

    public function __toString(): string
    {
        return sprintf('%s with 0-%d second jitter', $this->trigger, $this->maxSeconds);
    }

    public function getNextRunDate(\DateTimeImmutable $now): \DateTimeImmutable|null
    {
        return $this->trigger->getNextRunDate($now)->modify(sprintf('+%d seconds', random_int(0, $this->maxSeconds)));
    }
}
