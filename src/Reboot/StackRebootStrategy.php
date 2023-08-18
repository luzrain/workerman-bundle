<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Reboot;

final class StackRebootStrategy implements RebootStrategyInterface
{
    /**
     * @param iterable<RebootStrategyInterface> $strategies
     */
    public function __construct(private iterable $strategies)
    {
    }

    public function shouldReboot(): bool
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->shouldReboot()) {
                return true;
            }
        }

        return false;
    }
}
