<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Command;

use Luzrain\WorkermanBundle\ExtendedWorker as Worker;
use Luzrain\WorkermanBundle\KernelRunner;
use Luzrain\WorkermanBundle\Utils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class StopCommand extends Command implements SignalableCommandInterface
{
    public function __construct(
        private KernelRunner $kernelRunner,
        private string $pidFile,
    ) {
        parent::__construct();
    }

    public static function getDefaultName(): string
    {
        return 'workerman:stop';
    }

    public static function getDefaultDescription(): string
    {
        return 'Stop workerman server';
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT];
    }

    public function handleSignal(int|false $previousExitCode): int|false
    {
        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var ConsoleOutput $output */

//        $pid = Utils::getPid($this->pidFile);
//
//        if (!Worker::checkMasterIsAlive($pid)) {
//            $output->writeln('Workerman server is not running');
//            return self::FAILURE;
//        }
//
//        $output->writeln('Workerman server is stopping...');

        $this->kernelRunner
            ->setOutputStream($output->getStream())
            ->stop();

        //$output->writeln('Workerman server stop success');

        return self::SUCCESS;
    }
}
