<?php

declare(strict_types=1);

namespace Phenix\Cache;

use Phenix\Cache\Constants\Store;
use Phenix\Facades\Config as Configuration;

class CacheConfig
{
    private array $config;

    public function __construct()
    {
        $this->config = Configuration::get('cache', []);
    }

    public function default(): string
    {
        return $this->config['default'] ?? Store::LOCAL->value;
    }

    public function getStore(string|null $storeName = null): array
    {
        $storeName ??= $this->default();

        return $this->config['stores'][$storeName] ?? [];
    }

    public function getConnection(): string
    {
        return $this->getStore()['connection'] ?? 'default';
    }

    public function prefix(): string
    {
        return $this->config['prefix'] ?? '';
    }

    public function defaultTtlMinutes(): int
    {
        return (int) ($this->config['ttl'] ?? 60);
    }
}
