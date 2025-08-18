<?php

declare(strict_types=1);

include __DIR__ . '/../../vendor/autoload.php';

\workerman_start();
\register_shutdown_function(\workerman_stop(...));

function workerman_create_command(string $command): string
{
    return \sprintf('exec %s %s/index.php %s', PHP_BINARY, __DIR__, $command);
}

function workerman_start(): void
{
    $descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    \proc_open(\workerman_create_command('start -d'), $descriptor, $pipes);
    \usleep(100000);
}

function workerman_stop(): void
{
    \shell_exec(\workerman_create_command('stop'));
    @unlink(__DIR__ . '/../../var/task_status.log');
}
