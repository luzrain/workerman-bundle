<?php

declare(strict_types=1);

use Luzrain\WorkermanBundle\Runtime;
use Luzrain\WorkermanBundle\Test\App\Kernel;

$_SERVER['APP_RUNTIME'] = Runtime::class;

require_once dirname(__DIR__, 2) . '/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
