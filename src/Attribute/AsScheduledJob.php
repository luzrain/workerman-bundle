<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsScheduledJob
{
    public function __construct(
        public string|null $name = null,
        public string|null $schedule = null,
        public string|null $method = null,
    ) {
    }
}
