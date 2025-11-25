<?php

declare(strict_types=1);

namespace Phenix\Cache\Stores;

use Amp\Cache\LocalCache;
use Closure;
use Phenix\Cache\CacheStore;
use Phenix\Util\Date;

class LocalStore extends CacheStore
{
    public function __construct(
        protected LocalCache $cache,
        protected int $ttl = 60
    ) {
    }

    public function get(string $key, Closure|null $callback = null): mixed
    {
        $value = $this->cache->get($key);

        if ($value === null && $callback !== null) {
            $value = $callback();

            $this->set($key, $value);
        }

        return $value;
    }

    public function set(string $key, mixed $value, Date|null $ttl = null): void
    {
        $ttl ??= Date::now()->addMinutes($this->ttl);
        $seconds = Date::now()->diffInSeconds($ttl);

        $this->cache->set($key, $value, (int) $seconds);
    }

    public function forever(string $key, mixed $value): void
    {
        $this->cache->set($key, $value, null);
    }

    public function has(string $key): bool
    {
        return $this->cache->get($key) !== null;
    }

    public function delete(string $key): void
    {
        $this->cache->delete($key);
    }

    public function clear(): void
    {
        foreach ($this->cache->getIterator() as $key => $value) {
            $this->cache->delete($key);
        }
    }
}
