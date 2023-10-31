<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Reboot\Strategy;

interface RebootStrategyInterface
{
    public function shouldReboot(): bool;
}
