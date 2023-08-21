<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Test\App;

use Luzrain\WorkermanBundle\Attribute\AsProcess;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsProcess(name: 'Test process')]
final class Process
{
    public function __construct(
        #[Autowire(value: '%kernel.project_dir%/var/process_status.log')]
        private string $statusFile,
    ) {
    }

    public function __invoke(): void
    {
        file_put_contents($this->statusFile, time());
    }
}
