<?php

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProcessTest extends KernelTestCase
{
    public function testProcessIsLive(): void
    {
        $content = $this->getJobStatusFileContent() ?? $this->fail('Process status file is not found');

        $this->assertTrue((int) $content > time() - 4, 'Process started more than 4 seconds ago');
    }

    private function getJobStatusFileContent(): string|null
    {
        $i = 0;
        do {
            if (($content = @file_get_contents(dirname(__DIR__) . '/var/process_status.log')) !== false) {
                return $content;
            }
            usleep(200000);
        } while (++$i < 10);
        return null;
    }
}
