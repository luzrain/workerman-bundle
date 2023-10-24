<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Command;

use Luzrain\WorkermanBundle\KernelRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class StatusCommand extends Command
{
    public function __construct(private KernelRunner $kernelRunner)
    {
        parent::__construct();
    }

    public static function getDefaultName(): string
    {
        return 'workerman:status';
    }

    public static function getDefaultDescription(): string
    {
        return 'Get status';
    }

    protected function configure(): void
    {
        $this->addOption('live', 'l', InputOption::VALUE_NONE, 'Live mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $argv;
        $argv[1] = 'status';
        if ($input->getOption('live')) {
            $argv[2] = '-d';
        }

        return $this->kernelRunner->run();
    }
}
