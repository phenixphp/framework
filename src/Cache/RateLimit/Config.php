<?php

declare(strict_types=1);

namespace Phenix\Cache\RateLimit;

use Phenix\Facades\Config as Configuration;

class Config
{
    private array $config;

    public function __construct()
    {
        $this->config = Configuration::get('cache.rate_limit', []);
    }

    public function default(): string
    {
        return $this->config['driver'] ?? 'local';
    }

    public function perMinute(): int
    {
        return (int) ($this->config['per_minute'] ?? 60);
    }

    public function connection(): string
    {
        return $this->config['connection'] ?? 'default';
    }

    public function ttl(): int
    {
        return 60;
    }
}
