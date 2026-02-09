<?php

declare(strict_types=1);

namespace Phenix\Cache\RateLimit;

use Kelunik\RateLimit\RateLimit;

class RateLimitManager
{
    protected RateLimitConfig $config;

    protected array $rateLimiters = [];

    public function __construct(RateLimitConfig|null $config = null)
    {
        $this->config = $config ?? new RateLimitConfig();
    }

    public function get(string $key): int
    {
        return $this->limiter()->get($key);
    }

    public function increment(string $key): int
    {
        return $this->limiter()->increment($key);
    }

    public function getTtl(string $key): int
    {
        return $this->limiter()->getTtl($key);
    }

    public function limiter(): RateLimit
    {
        return $this->rateLimiters[$this->config->default()] ??= $this->resolveStore();
    }

    public function prefixed(string $prefix): self
    {
        $this->rateLimiters[$this->config->default()] = RateLimitFactory::withPrefix($this->limiter(), $prefix);

        return $this;
    }

    protected function resolveStore(): RateLimit
    {
        return match ($this->config->default()) {
            'redis' => RateLimitFactory::redis($this->config->ttl(), $this->config->connection()),
            'local' => RateLimitFactory::local($this->config->ttl()),
            default => RateLimitFactory::local($this->config->ttl()),
        };
    }
}
