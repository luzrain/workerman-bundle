<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Scheduler;

interface TriggerInterface extends \Stringable
{
    public function getNextRunDate(\DateTimeImmutable $run): \DateTimeImmutable|null;
}
