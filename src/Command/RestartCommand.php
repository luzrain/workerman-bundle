<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Command;

use Luzrain\WorkermanBundle\KernelRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class RestartCommand extends Command
{
    public function __construct(private KernelRunner $kernelRunner)
    {
        parent::__construct();
    }

    public static function getDefaultName(): string
    {
        return 'workerman:restart';
    }

    public static function getDefaultDescription(): string
    {
        return 'Restart server';
    }

    protected function configure(): void
    {
        $this->addOption('daemon', 'd', InputOption::VALUE_NONE, 'Daemon mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $argv;
        $argv[1] = 'restart';
        if ($input->getOption('daemon')) {
            $argv[2] = '-d';
        }

        return $this->kernelRunner->run();
    }
}
