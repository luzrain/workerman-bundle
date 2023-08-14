<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\SchedulerTrigger;

interface TriggerInterface extends \Stringable
{
    public function getNextRunDate(\DateTimeImmutable $now): \DateTimeImmutable|null;
}
