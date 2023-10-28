<?php

if (!isset(
    $_SERVER['WORKERMAN_PROJECT_DIR'],
    $_SERVER['WORKERMAN_KERNEL_CLASS'],
    $_SERVER['WORKERMAN_APP_ENV'],
    $_SERVER['WORKERMAN_APP_DEBUG'],
)) {
    throw new \Exception('This ENV variables should be defined: WORKERMAN_PROJECT_DIR, WORKERMAN_KERNEL_CLASS, WORKERMAN_APP_ENV, WORKERMAN_APP_DEBUG');
}

require_once $_SERVER['WORKERMAN_PROJECT_DIR'] . '/vendor/autoload.php';

$runtime = new \Luzrain\WorkermanBundle\Runtime([
    'project_dir' => $_SERVER['WORKERMAN_PROJECT_DIR'],
    'env' => $_SERVER['WORKERMAN_APP_ENV'],
    'debug' => $_SERVER['WORKERMAN_APP_DEBUG'],
    'extended_interface' => true,
]);

$kernel = $_SERVER['WORKERMAN_KERNEL_CLASS'];
$app = fn (array $context) => new $kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
[$app, $args] = $runtime->getResolver($app)->resolve();
$runner = $runtime->getRunner($app(...$args));

unset($_SERVER['WORKERMAN_PROJECT_DIR']);
unset($_SERVER['WORKERMAN_KERNEL_CLASS']);
unset($_SERVER['WORKERMAN_APP_ENV']);
unset($_SERVER['WORKERMAN_APP_DEBUG']);
putenv('WORKERMAN_PROJECT_DIR');
putenv('WORKERMAN_KERNEL_CLASS');
putenv('WORKERMAN_APP_ENV');
putenv('WORKERMAN_APP_DEBUG');

exit($runner->run());
