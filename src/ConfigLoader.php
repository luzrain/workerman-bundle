<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

final class ConfigLoader implements CacheWarmerInterface
{
    private array $config;
    private ConfigCache $cache;
    private string $yamlConfigFilePath;

    public function __construct(string $projectDir, string $cacheDir, bool $isDebug)
    {
        $this->yamlConfigFilePath = sprintf('%s/config/packages/workerman.yaml', $projectDir);
        $cacheConfigFilePath = sprintf('%s/workerman/config.cache.php', $cacheDir);
        $this->cache = new ConfigCache($cacheConfigFilePath, $isDebug);
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, string $buildDir = null): array
    {
        $resources = is_file($this->yamlConfigFilePath) ? [new FileResource($this->yamlConfigFilePath)] : [];
        $this->cache->write(sprintf('<?php return %s;', var_export($this->config, true)), $resources);

        return [];
    }

    public function isFresh(): bool
    {
        return $this->cache->isFresh();
    }

    private function getConfig(): array
    {
        return $this->config ??= require $this->cache->getPath();
    }

    public function setWorkermanConfig(array $config): void
    {
        $this->config[0] = $config;
    }

    public function setProcessConfig(array $config): void
    {
        $this->config[1] = $config;
    }

    public function setSchedulerConfig(array $config): void
    {
        $this->config[2] = $config;
    }

    public function getWorkermanConfig(): array
    {
        return $this->getConfig()[0];
    }

    public function getProcessConfig(): array
    {
        return $this->getConfig()[1];
    }

    public function getSchedulerConfig(): array
    {
        return $this->getConfig()[2];
    }
}
