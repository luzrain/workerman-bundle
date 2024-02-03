# Workerman runtime for symfony applications
![PHP ^8.1](https://img.shields.io/badge/PHP-^8.1-777bb3.svg?style=flat)
![Symfony ^6.4|^7.0](https://img.shields.io/badge/Symfony-^6.4|^7.0-374151.svg?style=flat)
[![Tests Status](https://img.shields.io/github/actions/workflow/status/luzrain/workerman-bundle/tests.yaml?branch=master)](../../actions/workflows/tests.yaml)

[Workerman](https://github.com/walkor/workerman) is a high-performance, asynchronous event-driven PHP framework written in pure PHP.  
This bundle provides a Workerman integration in Symfony, allowing you to easily create a http server, scheduler and supervisor all in one place.
This bundle allows you to replace a traditional web application stack like php-fpm + nginx + cron + supervisord, all written in pure PHP (no Go, no external binaries).
The request handler works in an event loop which means the Symfony kernel and the dependency injection container are preserved between requests,
making your application faster with less (or no) code changes.

## Getting started
### Install composer packages
```bash
$ composer require luzrain/workerman-bundle nyholm/psr7
```

### Enable the bundle
```php
<?php
// config/bundles.php

return [
    // ...
    Luzrain\WorkermanBundle\WorkermanBundle::class => ['all' => true],
];
```

### Configure the bundle
A minimal configuration might look like this.  
For all available options with documentation, see the command output.
```bash
$ bin/console config:dump-reference workerman
```

```yaml
# config/packages/workerman.yaml

workerman:
  servers:
    - name: 'Symfony webserver'
      listen: http://0.0.0.0:80
      processes: 4

  reload_strategy:
    exception:
      active: true

    file_monitor:
      active: true
```

### Start application
```bash
$ bin/console workerman:start
```
or
```bash
$ APP_RUNTIME=Luzrain\\WorkermanBundle\\Runtime php public/index.php start
```

\* For better performance, Workerman recommends installing the _php-event_ extension.

## Reload strategies
Because of the asynchronous nature of the server, the workers reuse loaded resources on each request. This means that in some cases we need to restart workers.  
For example, after an exception is thrown, to prevent services from being in an unrecoverable state. Or every time you change the code in the IDE.  
There are a few restart strategies that are implemented and can be enabled or disabled depending on the environment.

 - **exception**  
   Reload worker each time that an exception is thrown during the request handling.
 - **max_requests**  
   Reload worker on every N request to prevent memory leaks.
 - **file_monitor**  
   Reload all workers each time you change the files**.
 - **always**  
   Reload worker after each request.

** It is highly recommended to install the _php-inotify_ extension for file monitoring. Without it, monitoring will work in polling mode, which can be very cpu and disk intensive for large projects.

See all available options for each strategy in the command output.
```bash
$ bin/console config:dump-reference workerman reload_strategy
```

### Implement your own reload strategies
You can create reload strategy with your own logic by implementing the RebootStrategyInterface and adding the `workerman.reboot_strategy` tag to the service.
```php
<?php

use Luzrain\WorkermanBundle\Reboot\RebootStrategyInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('workerman.reboot_strategy')]
final class TestRebootStrategy implements RebootStrategyInterface
{
    public function shouldReboot(): bool
    {
        return true;
    }
}
```

## Scheduler
Periodic tasks can be configured with attributes or with tags in configuration files.  
Schedule string can be formatted in several ways:  
 - An integer to define the frequency as a number of seconds. Example: _60_
 - An ISO8601 datetime format. Example: _2023-08-01T01:00:00+08:00_
 - An ISO8601 duration format. Example: _PT1M_
 - A relative date format as supported by DateInterval. Example: _1 minutes_
 - A cron expression**. Example: _*/1 * * * *_

** Note that you need to install the [dragonmantank/cron-expression](https://github.com/dragonmantank/cron-expression) package if you want to use cron expressions as schedule strings

```php
<?php

use Luzrain\WorkermanBundle\Attribute\AsTask;

/**
 * Attribute parameters
 * name: Task name
 * schedule: Task schedule in any format
 * method: method to call, __invoke by default
 * jitter: Maximum jitter in seconds that adds a random time offset to the schedule. Use to prevent multiple tasks from running at the same time
 */
#[AsTask(name: 'My scheduled task', schedule: '1 minutes')]
final class TaskService
{
    public function __invoke()
    {
        // ...
    }
}
```

```yaml
# config/services.yaml

services:
  App\TaskService:
    tags:
      - { name: '', schedule: '1 minutes' }
```

## Supervisor
Supervisor can be configured with attributes or with tags in configuration files.  
Processes are kept alive and wake up if one of them dies.

```php
<?php

use Luzrain\WorkermanBundle\Attribute\AsProcess;

/**
 * Attribute parameters
 * name: Process name
 * processes: number of processes
 * method: method to call, __invoke by default
 */
#[AsProcess(name: 'My worker', processes: 1)]
final class ProcessService
{
    public function __invoke()
    {
        // ...
    }
}
```

```yaml
# config/services.yaml

services:
  App\ProcessService:
    tags:
      - { name: 'workerman.process', processes: 1 }
```
