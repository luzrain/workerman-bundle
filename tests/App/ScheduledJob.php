<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Test\App;

use Luzrain\WorkermanBundle\Attribute\AsScheduledJob;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsScheduledJob(name: 'Test job', schedule: '1 second')]
final class ScheduledJob
{
    public function __construct(
        #[Autowire(value: '%kernel.project_dir%/var/job_status.log')]
        private string $statusFile,
    ) {
    }

    public function __invoke(): void
    {
        file_put_contents($this->statusFile, time());
    }
}
