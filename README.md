# Workerman runtime for symfony applications
[![PHP >=8.1](https://img.shields.io/badge/PHP->=8.1-777bb3.svg?style=flat)](https://www.php.net/releases/8.1/en.php)
![Symfony ^6.3](https://img.shields.io/badge/Symfony-^6.3-374151.svg?style=flat)
[![Tests Status](https://img.shields.io/github/actions/workflow/status/luzrain/workerman-bundle/tests.yaml?branch=master)](../../actions/workflows/tests.yaml)

Make symfony application faster, with less (or none) change with this bundle.
Run http server, background asynchronius processes, and periodic tasks from one place in pure PHP with power of [Workerman](https://www.workerman.net/) framework.

## Installation
### Install composer package
```bash
$ composer require luzrain/workerman-bundle (todo)
```

### Enable the Bundle
```php
<?php
// config/bundles.php

return [
    // ...
    Luzrain\WorkermanBundle\WorkermanBundle::class => ['all' => true],
];
```

### Configure bundle
```yaml
# config/packages/workerman.yaml

workerman:
  server:
    # Name of the worker processes
    name: 'Symfony Workerman Server'
    # Listen address (can be http or https)
    listen: http://0.0.0.0:80
    # Path to local certificate file on filesystem. Necessary if listen address is https
    #local_cert: '%kernel.project_dir%/crt/localhost.crt'
    # Path to local private key file on filesystem. Necessary if listen address is https
    #local_pk: '%kernel.project_dir%/crt/localhost.key'
    # Number of worker processes (default is the number of cpu cores*2 in the prod, and 1 in other env)
    processes: 1
    # Unix user of process
    user: app
    # Unix group of process
    group: app

```

### Start application
```bash
$ APP_RUNTIME=Luzrain\\WorkermanBundle\\Runtime php public/index.php start
```
