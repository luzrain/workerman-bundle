<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

final class ConfigLoader implements CacheWarmerInterface
{
    private ConfigCache $cache;
    private array $workermanConfig = [];
    private array $processConfig = [];
    private array $schedulerConfig = [];

    public function __construct(string $cacheDir, bool $isDebug)
    {
        $this->cache = new ConfigCache(sprintf('%s/workerman/config.cache.php', $cacheDir), $isDebug);

        if (is_file($this->cache->getPath())) {
            $config = require $this->cache->getPath();
            $this->workermanConfig = $config[0];
            $this->processConfig = $config[1];
            $this->schedulerConfig = $config[2];
        }
    }

    public function setWorkermanConfig(array $config): void
    {
        $this->workermanConfig = $config;
    }

    public function setProcessConfig(array $config): void
    {
        $this->processConfig = $config;
    }

    public function setSchedulerConfig(array $config): void
    {
        $this->schedulerConfig = $config;
    }

    public function getWorkermanConfig(): array
    {
        return $this->workermanConfig;
    }

    public function getProcessConfig(): array
    {
        return $this->processConfig;
    }

    public function getSchedulerConfig(): array
    {
        return $this->schedulerConfig;
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir): array
    {
        $config = [
            0 => $this->workermanConfig,
            1 => $this->processConfig,
            2 => $this->schedulerConfig,
        ];
        $this->cache->write(sprintf('<?php return %s;', var_export($config, true)), []);
        return [];
    }
}
