<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Reboot;

interface RebootStrategyInterface
{
    public function shouldReboot(): bool;
}
