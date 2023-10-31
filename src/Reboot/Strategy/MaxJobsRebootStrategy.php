<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Reboot\Strategy;

final class MaxJobsRebootStrategy implements RebootStrategyInterface
{
    private int $jobsCount = 0;
    private int $maxJobs;

    public function __construct(int $maxJobs, int $dispersion = 0)
    {
        $minJobs = $maxJobs - (int) round($maxJobs * $dispersion / 100);
        $this->maxJobs = random_int($minJobs, $maxJobs);
    }

    public function shouldReboot(): bool
    {
        return ++$this->jobsCount > $this->maxJobs;
    }
}
