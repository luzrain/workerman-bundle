<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Command;

use Luzrain\WorkermanBundle\KernelRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class StopCommand extends Command
{
    public function __construct(private KernelRunner $kernelRunner)
    {
        parent::__construct();
    }

    public static function getDefaultName(): string
    {
        return 'workerman:stop';
    }

    public static function getDefaultDescription(): string
    {
        return 'Stop server';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $argv;
        $argv[1] = 'stop';

        return $this->kernelRunner->run();
    }
}
