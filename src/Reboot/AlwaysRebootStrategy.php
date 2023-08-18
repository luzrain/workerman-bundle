<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Reboot;

final class AlwaysRebootStrategy implements RebootStrategyInterface
{
    public function shouldReboot(): bool
    {
        return true;
    }
}
