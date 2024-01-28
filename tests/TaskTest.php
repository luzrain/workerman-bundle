<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TaskTest extends KernelTestCase
{
    public function testTaskIsRunning(): void
    {
        $content = $this->getTaskStatusFileContent() ?? $this->fail('Task status file is not found');

        $this->assertTrue((int) $content > time() - 4, 'Task was called more than 4 seconds ago');
    }

    private function getTaskStatusFileContent(): string|null
    {
        $i = 0;
        do {
            if (($content = @file_get_contents(dirname(__DIR__) . '/var/task_status.log')) !== false) {
                return $content;
            }
            usleep(200000);
        } while (++$i < 10);
        return null;
    }
}
