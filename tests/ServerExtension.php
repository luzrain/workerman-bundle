<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Test;

use Luzrain\WorkermanBundle\Runtime;
use PHPUnit\Event\TestRunner\Finished;
use PHPUnit\Event\TestRunner\FinishedSubscriber;
use PHPUnit\Event\TestRunner\Started;
use PHPUnit\Event\TestRunner\StartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class ServerExtension implements Extension
{
    private const SERVER_START_COMMAND = 'APP_RUNTIME=%s %s tests/App/index.php start';
    private const SERVER_STOP_COMMAND = 'APP_RUNTIME=%s %s tests/App/index.php stop';

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscribers(
            new class($this) implements StartedSubscriber {
                public function __construct(private ServerExtension $extension) {}
                public function notify(Started $event): void
                {
                    $this->extension->onTestsStart();
                }
            },
            new class($this) implements FinishedSubscriber {
                public function __construct(private ServerExtension $extension) {}
                public function notify(Finished $event): void
                {
                    $this->extension->onTestsStop();
                }
            },
        );
    }

    public function onTestsStart(): void
    {
        exec(sprintf(self::SERVER_START_COMMAND, escapeshellarg(Runtime::class), PHP_BINARY) . ' > /dev/null 2>&1 &');
    }

    public function onTestsStop(): void
    {
        exec(sprintf(self::SERVER_STOP_COMMAND, escapeshellarg(Runtime::class), PHP_BINARY));
    }
}
