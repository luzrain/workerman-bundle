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
A minimal configuration might look like this.  
For all available options, see the command output.
```bash
$ console config:dump-reference workerman
```

```yaml
# config/packages/workerman.yaml

workerman:
  webserver:
    name: 'Symfony Workerman Server'
    listen: http://0.0.0.0:80
    processes: 8
    relod_strategy: [exception, file_monitor]
```

### Start application
```bash
$ APP_RUNTIME=Luzrain\\WorkermanBundle\\Runtime php public/index.php start
```
