<?php

declare(strict_types=1);

namespace Luzrain\WorkermanBundle;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

final class ConfigLoader implements CacheWarmerInterface
{
    private array $config = [];
    private ConfigCache $cache;

    public function __construct(private string $cacheDir, bool $isDebug)
    {
        $this->cache = new ConfigCache(sprintf('%s/workerman/config.cache.php', $this->cacheDir), $isDebug);
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
        $this->warmUp($this->cacheDir);
    }

    public function getConfig(): array
    {
        return require $this->cache->getPath();
    }

    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp(string $cacheDir): array
    {
        $this->cache->write(sprintf('<?php return %s;', var_export($this->config, true)), []);
        return [];
    }
}
