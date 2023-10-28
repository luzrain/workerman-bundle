<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Command;

use Luzrain\WorkermanBundle\KernelRunner;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Luzrain\WorkermanBundle\Utils;

final class StartCommand extends Command implements SignalableCommandInterface
{
    public function __construct(
        private KernelRunner $kernelRunner,
        private LoggerInterface $logger,
        private string $pidFile,
    ) {
        parent::__construct();
    }

    public static function getDefaultName(): string
    {
        return 'workerman:start';
    }

    public static function getDefaultDescription(): string
    {
        return 'Start workerman server';
    }

    protected function configure(): void
    {
        $this->addOption('daemon', 'd', InputOption::VALUE_NONE, 'Daemon mode');
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int|false $previousExitCode): int|false
    {
        if ($previousExitCode === SIGTERM) {
            posix_kill(Utils::getPid($this->pidFile), SIGTERM);
        }

        echo "\n";
        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = Utils::getPid($this->pidFile);

        if ($pid !== 0) {
            $output->writeln('Workerman server already running');
            return self::FAILURE;
        }

        $kernel = $this->getApplication()->getKernel();
        $isDaemon = $input->getOption('daemon');

        $this->kernelRunner->runStart($isDaemon);

        $headerRendered = false;
        foreach ($this->kernelRunner->readOutput() as $line) {
            if (!$headerRendered && str_starts_with($line, 'HEADER:')) {
                $headerRendered = true;
                $workers = unserialize(substr($line, 7));

                $output->write('Workerman start. ');
                $output->write('Environment: <comment>' . $kernel->getEnvironment() . '</comment> ');
                $output->write('Debug: <comment>' . ($kernel->isDebug() ? 'true' : 'false') . '</comment>');
                $output->writeln('');

                (new Table($output))
                    ->setHeaders(['User', 'Worker', 'Listen', 'Processes'])
                    ->setRows($workers)
                    ->render();

                if (!$isDaemon && !$output->isVeryVerbose()) {
                    $output->writeln('Re-run the command with a -vv option to see logs.');
                }

                continue;
            }

            if (!$headerRendered) {
                continue;
            }

            if (str_starts_with($line, 'LOG:')) {
                $pos =  stripos($line, ':', 4);
                $level = strtolower(substr($line, 4, $pos - 4));
                $string = unserialize(substr($line, $pos + 1));
                $this->logger->log($level, $string);
                continue;
            }

            $output->writeln($line);
        }

        return self::SUCCESS;
    }
}
