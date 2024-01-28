<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Command;

use Luzrain\WorkermanBundle\ExtendedWorker as Worker;
use Luzrain\WorkermanBundle\KernelRunner;
use Luzrain\WorkermanBundle\Utils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class StatusCommand extends Command implements SignalableCommandInterface
{
    public function __construct(
        private KernelRunner $kernelRunner,
        private string $pidFile,
    ) {
        parent::__construct();
    }

    public static function getDefaultName(): string
    {
        return 'workerman:status';
    }

    public static function getDefaultDescription(): string
    {
        return 'Get workerman status';
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = Utils::getPid($this->pidFile);

        if (!Worker::checkMasterIsAlive($pid)) {
            $output->writeln('Workerman server is not running');
            return self::FAILURE;
        }

        throw new \Exception('Not implemented yet');

        //        $this->kernelRunner->runStatus();
        //        foreach ($this->kernelRunner->readOutput() as $line) {
        //            $output->writeln($line);
        //        }

        return self::SUCCESS;
    }
}
