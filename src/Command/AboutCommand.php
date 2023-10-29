<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Command;

use Luzrain\WorkermanBundle\ExtendedWorker as Worker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

final class AboutCommand extends Command
{
    public static function getDefaultName(): string
    {
        return 'workerman:about';
    }

    public static function getDefaultDescription(): string
    {
        return 'Display information about workerman';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var KernelInterface $kernel */
        $kernel = $this->getApplication()->getKernel();

        $rows = [
            ['<info>Workerman</info>'],
            new TableSeparator(),
            ['PHP version', PHP_VERSION],
            ['Workerman version', Worker::VERSION],
            ['Event-Loop', Worker::getEventLoopClass()],
        ];

        $io->table([], $rows);

        return self::SUCCESS;
    }

    private function renderTable(OutputInterface $output, array $info): void
    {
        $table = (new Table($output))
            ->setHeaders(['user', 'worker', 'listen', 'processes'])
            ->setRows($info['workers'])
        ;
        $table->render();
    }
}
