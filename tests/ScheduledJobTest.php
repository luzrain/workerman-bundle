<?php

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ScheduledJobTest extends KernelTestCase
{
    public function testScheduledJobIsRunning(): void
    {
        $content = $this->getJobStatusFileContent() ?? $this->fail('Job status file is not found');

        $this->assertTrue((int) $content > time() - 4, 'Job was called more than 4 seconds ago');
    }

    private function getJobStatusFileContent(): string|null
    {
        $i = 0;
        do {
            if (($content = @file_get_contents(dirname(__DIR__) . '/var/job_status.log')) !== false) {
                return $content;
            }
            usleep(200000);
        } while (++$i < 10);
        return null;
    }
}
