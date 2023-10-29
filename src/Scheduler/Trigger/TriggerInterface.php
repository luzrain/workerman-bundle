<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Scheduler\Trigger;

interface TriggerInterface extends \Stringable
{
    public function getNextRunDate(\DateTimeImmutable $now): \DateTimeImmutable|null;
}
