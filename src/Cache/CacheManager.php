<?php

declare(strict_types=1);

namespace Phenix\Cache;

use Amp\Cache\LocalCache;
use Closure;
use Phenix\Cache\Constants\Store;
use Phenix\Cache\Contracts\CacheStore;
use Phenix\Cache\Stores\FileStore;
use Phenix\Cache\Stores\LocalStore;
use Phenix\Cache\Stores\RedisStore;
use Phenix\Facades\Redis;
use Phenix\Util\Date;

class CacheManager
{
    protected array $stores = [];

    protected CacheConfig $config;

    public function __construct(CacheConfig|null $config = null)
    {
        $this->config = $config ?? new CacheConfig();
    }

    public function store(Store|null $storeName = null): CacheStore
    {
        $storeName ??= $this->resolveStoreName($storeName);

        return $this->stores[$storeName->value] ??= $this->resolveStore($storeName);
    }

    public function get(string $key, Closure|null $callback = null): mixed
    {
        return $this->store()->get($key, $callback);
    }

    public function set(string $key, mixed $value, Date|null $ttl = null): void
    {
        $this->store()->set($key, $value, $ttl);
    }

    public function forever(string $key, mixed $value): void
    {
        $this->store()->forever($key, $value);
    }

    public function remember(string $key, Date $ttl, Closure $callback): mixed
    {
        return $this->store()->remember($key, $ttl, $callback);
    }

    public function rememberForever(string $key, Closure $callback): mixed
    {
        return $this->store()->rememberForever($key, $callback);
    }

    public function has(string $key): bool
    {
        return $this->store()->has($key);
    }

    public function delete(string $key): void
    {
        $this->store()->delete($key);
    }

    public function clear(): void
    {
        $this->store()->clear();
    }

    protected function resolveStoreName(Store|null $storeName = null): Store
    {
        return $storeName ?? Store::from($this->config->default());
    }

    protected function resolveStore(Store $storeName): CacheStore
    {
        return match ($storeName) {
            Store::LOCAL => $this->createLocalStore(),
            Store::FILE => $this->createFileStore(),
            Store::REDIS => $this->createRedisStore(),
        };
    }

    protected function createLocalStore(): CacheStore
    {
        $storeConfig = $this->config->getStore(Store::LOCAL->value);

        $cache = new LocalCache($storeConfig['size_limit'] ?? null, $storeConfig['gc_interval'] ?? 5);

        $defaultTtl = (int) ($storeConfig['ttl'] ?? $this->config->defaultTtlMinutes());

        return new LocalStore($cache, $defaultTtl);
    }

    protected function createFileStore(): CacheStore
    {
        $storeConfig = $this->config->getStore(Store::FILE->value);

        $path = $storeConfig['path'] ?? base_path('storage' . DIRECTORY_SEPARATOR . 'cache');

        $defaultTtl = (int) ($storeConfig['ttl'] ?? $this->config->defaultTtlMinutes());

        return new FileStore($path, $this->config->prefix(), $defaultTtl);
    }

    protected function createRedisStore(): CacheStore
    {
        $storeConfig = $this->config->getStore(Store::REDIS->value);
        $defaultTtl = $storeConfig['ttl'] ?? $this->config->defaultTtlMinutes();

        $client = Redis::connection($this->config->getConnection())->client();

        return new RedisStore($client, $this->config->prefix(), (int) $defaultTtl);
    }
}
