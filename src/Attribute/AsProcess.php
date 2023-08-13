<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsProcess
{
    public function __construct(
        public string|null $name = null,
        public int|null $processes = null,
        public string|null $method = null,
    ) {
    }
}
